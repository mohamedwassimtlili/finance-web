<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;

class HuggingFaceService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $apiKey
    ) {
    }

    public function generateSideHustles(string $userDescription): array
    {
        $cacheKey = 'side_hustle_' . md5($userDescription);
        
        return $this->cache->get($cacheKey, function () use ($userDescription) {
            $prompt = $this->buildPrompt($userDescription);
            
            try {
                $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.3', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'inputs' => $prompt,
                        'parameters' => [
                            'max_new_tokens' => 2000,
                            'temperature' => 0.7,
                            'return_full_text' => false,
                        ]
                    ]
                ]);
                
                $statusCode = $response->getStatusCode();
                
                // Log the status code
                error_log('HuggingFace API Status Code: ' . $statusCode);
                
                if ($statusCode !== 200) {
                    error_log('HuggingFace API Error: ' . $response->getContent(false));
                    return $this->getDefaultSuggestions($userDescription);
                }
                
                $data = $response->toArray();
                error_log('HuggingFace API Response: ' . json_encode($data));
                
                $text = $data[0]['generated_text'] ?? '';
                
                // Clean the response
                $text = preg_replace('/```json\n?|```/', '', $text);
                $text = trim($text);
                
                // Try to extract JSON
                if (preg_match('/\[[\s\S]*\]/', $text, $matches)) {
                    $result = json_decode($matches[0], true);
                    if ($result && count($result) > 0) {
                        return $result;
                    }
                }
                
                return $this->getDefaultSuggestions($userDescription);
                
            } catch (\Exception $e) {
                error_log('HuggingFace API Exception: ' . $e->getMessage());
                return $this->getDefaultSuggestions($userDescription);
            }
        }, 3600);
    }

    private function buildPrompt(string $userDescription): string
    {
        return "You are a side hustle expert. Based on this user: \"$userDescription\", suggest 3 side hustles.

Return ONLY valid JSON array. Each item must have: title, why_fits, earning_potential, difficulty (Easy/Medium/Hard), time_commitment, deep_explain (200 words).

Example: [{\"title\":\"Freelance Writing\",\"why_fits\":\"You have great communication skills\",\"earning_potential\":\"$500-2000/month\",\"difficulty\":\"Easy\",\"time_commitment\":\"2-5 hours/week\",\"deep_explain\":\"Step by step guide to start freelance writing...\"}]";
    }

    private function getDefaultSuggestions(string $userDescription): array
    {
        // This is fake data - we need REAL API to work!
        return [
            [
                'title' => 'Freelance Writing',
                'why_fits' => 'Your communication skills are valuable online',
                'earning_potential' => '$500-2000/month',
                'difficulty' => 'Easy',
                'time_commitment' => '2-5 hours/week',
                'deep_explain' => 'Create a profile on Upwork. Write 3 sample articles. Apply to 5 jobs per week.'
            ],
            [
                'title' => 'Virtual Assistant',
                'why_fits' => 'Your organizational skills are in demand',
                'earning_potential' => '$300-1500/month',
                'difficulty' => 'Easy',
                'time_commitment' => '1-4 hours/week',
                'deep_explain' => 'Sign up on Belay or Time Etc. Create a profile.'
            ],
            [
                'title' => 'Online Tutoring',
                'why_fits' => 'Your knowledge can help others learn',
                'earning_potential' => '$400-2000/month',
                'difficulty' => 'Medium',
                'time_commitment' => '3-6 hours/week',
                'deep_explain' => 'Sign up on Wyzant or TutorMe. Set your rate at $25-40/hour.'
            ]
        ];
    }
}