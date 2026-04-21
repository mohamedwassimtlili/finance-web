<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GeminiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $apiKey
    ) {
    }

    public function generateSideHustles(string $userDescription): array
    {
        // Check if API key is set
        if (empty($this->apiKey) || $this->apiKey === 'your_api_key_here') {
            // Return fake data for testing
            return $this->getFakeSuggestions($userDescription);
        }
        
        $cacheKey = 'side_hustle_' . md5($userDescription);
        
        return $this->cache->get($cacheKey, function () use ($userDescription) {
            $prompt = "Based on this user: \"$userDescription\", suggest 3 side hustles. Return ONLY valid JSON array. Each item must have: title, why_fits, earning_potential, difficulty (Easy/Medium/Hard), time_commitment, deep_explain (100 words). Example: [{\"title\":\"Dog Walking\",\"why_fits\":\"You love animals\",\"earning_potential\":\"$300/month\",\"difficulty\":\"Easy\",\"time_commitment\":\"3 hours/week\",\"deep_explain\":\"Step 1: Download Rover app. Step 2: Create profile...\"}]";
            
            try {
                $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', [
                    'query' => ['key' => $this->apiKey],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ]
                    ]
                ]);
                
                $statusCode = $response->getStatusCode();
                
                if ($statusCode !== 200) {
                    return $this->getFakeSuggestions($userDescription);
                }
                
                $data = $response->toArray();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $text = preg_replace('/```json\n?|```/', '', $text);
                
                $result = json_decode($text, true);
                
                if (empty($result) || !is_array($result)) {
                    return $this->getFakeSuggestions($userDescription);
                }
                
                return $result;
                
            } catch (\Exception $e) {
                return $this->getFakeSuggestions($userDescription);
            }
        }, 3600);
    }
    
    private function getFakeSuggestions(string $userDescription): array
    {
        // Detect keywords from user description
        $suggestions = [];
        
        if (stripos($userDescription, 'teacher') !== false || stripos($userDescription, 'tutor') !== false) {
            $suggestions[] = [
                'title' => 'Online Tutoring',
                'why_fits' => 'Your teaching skills are valuable online',
                'earning_potential' => '$400-2000/month',
                'difficulty' => 'Easy',
                'time_commitment' => '3-6 hours/week',
                'deep_explain' => 'Online tutoring is perfect for teachers. Sign up on Wyzant or TutorMe. Set your rate at $25-40/hour. Create a profile highlighting your teaching experience. Start with 3 students, then grow from there.'
            ];
        }
        
        if (stripos($userDescription, 'photography') !== false || stripos($userDescription, 'camera') !== false) {
            $suggestions[] = [
                'title' => 'Sell Photos Online',
                'why_fits' => 'Your photography can earn passive income',
                'earning_potential' => '$200-1000/month',
                'difficulty' => 'Medium',
                'time_commitment' => '2-4 hours/week',
                'deep_explain' => 'Upload your best photos to Shutterstock, Adobe Stock, or Etsy. Focus on popular categories like nature, business, or lifestyle. Each photo can sell hundreds of times. Start with 50 photos and add 10 new ones weekly.'
            ];
        }
        
        if (stripos($userDescription, 'nurse') !== false || stripos($userDescription, 'medical') !== false) {
            $suggestions[] = [
                'title' => 'Medical Transcription',
                'why_fits' => 'Your medical knowledge is valuable',
                'earning_potential' => '$500-2500/month',
                'difficulty' => 'Medium',
                'time_commitment' => '5-10 hours/week',
                'deep_explain' => 'Medical transcription pays well because it requires expertise. Sign up on Rev or TranscribeMe. Your nursing background gives you an advantage. Start with short audio files and work up to longer ones.'
            ];
        }
        
        if (stripos($userDescription, 'writing') !== false || stripos($userDescription, 'blog') !== false) {
            $suggestions[] = [
                'title' => 'Freelance Writing',
                'why_fits' => 'Your writing skills are in high demand',
                'earning_potential' => '$500-3000/month',
                'difficulty' => 'Easy',
                'time_commitment' => '2-5 hours/week',
                'deep_explain' => 'Create a profile on Upwork or Fiverr. Write 3 sample articles about topics you know. Apply to 5 beginner jobs at $25-50 per article. After 5 good reviews, raise your rates to $75+ per article.'
            ];
        }
        
        // Default suggestions if no keywords match
        if (empty($suggestions)) {
            $suggestions = [
                [
                    'title' => 'Virtual Assistant',
                    'why_fits' => 'Your organizational skills can be monetized',
                    'earning_potential' => '$300-1500/month',
                    'difficulty' => 'Easy',
                    'time_commitment' => '1-4 hours/week',
                    'deep_explain' => 'Virtual assistants help businesses with email, scheduling, and social media. Sign up on Belay or Time Etc. Create a profile highlighting your organizational skills. Start with 1-2 clients and grow from there.'
                ],
                [
                    'title' => 'User Testing',
                    'why_fits' => 'Your opinion as a user is valuable',
                    'earning_potential' => '$200-800/month',
                    'difficulty' => 'Easy',
                    'time_commitment' => '1-3 hours/week',
                    'deep_explain' => 'Companies pay for feedback on their websites and apps. Sign up on UserTesting.com or TryMyUI. Each test pays $10-30 for 15-20 minutes of work. Complete 2-3 tests per day for consistent income.'
                ],
                [
                    'title' => 'Rent Your Stuff',
                    'why_fits' => 'You own items others need temporarily',
                    'earning_potential' => '$100-500/month',
                    'difficulty' => 'Easy',
                    'time_commitment' => '1-2 hours/week',
                    'deep_explain' => 'List your camera, tools, or sports equipment on Fat Llama or PeerRenters. Set competitive prices based on item value. Take good photos and write clear descriptions. Respond quickly to rental requests.'
                ]
            ];
        }
        
        // Ensure we always have exactly 3 suggestions
        while (count($suggestions) < 3) {
            $suggestions[] = [
                'title' => 'Freelance Services',
                'why_fits' => 'Your skills can help businesses',
                'earning_potential' => '$300-1500/month',
                'difficulty' => 'Easy',
                'time_commitment' => '2-5 hours/week',
                'deep_explain' => 'List your skills on Fiverr. Create gigs for services like data entry, resume editing, or social media help. Start with low prices to get reviews, then raise rates after 10 positive reviews.'
            ];
        }
        
        return $suggestions;
    }
}