<?php

namespace App\Service;

class RulesBasedService
{
    public function generateSuggestions(array $formData): array
    {
        $workPreference = $formData['work_preference'] ?? 'hybrid';
        $personality = $formData['personality'] ?? 'ambivert';
        $riskTolerance = $formData['risk_tolerance'] ?? 'low';
        
        $skills = $formData['skills'] ?? [];
        if (is_string($skills)) {
            $skills = !empty($skills) ? explode(',', $skills) : [];
        }
        $skills = array_map('trim', $skills);
        
        $assets = $formData['assets'] ?? [];
        if (is_string($assets)) {
            $assets = !empty($assets) ? explode(',', $assets) : [];
        }
        $assets = array_map('trim', $assets);

        // Define our universe of side hustles with their scoring weights
        $hustles = [
            [
                'title' => 'Freelance Content Writer',
                'why_fits' => 'Perfect for focused, independent workers who want flexibility.',
                'earning_potential' => '$500-3000/month',
                'difficulty' => 'Medium',
                'time_commitment' => '5-15 hours/week',
                'deep_explain' => "Every business needs written content for their blogs and websites.\n\nStep 1: Write 3 sample articles on Medium.\nStep 2: Build a profile on Upwork or Fiverr.\nStep 3: Pitch to 5-10 beginner jobs at $25-$50/article.\nStep 4: Over-deliver and ask for 5-star reviews.\nStep 5: Raise your rates consistently.",
                'weights' => [
                    'work_pref' => ['remote' => 10, 'hybrid' => 5, 'in_person' => -10],
                    'personality' => ['introvert' => 10, 'ambivert' => 5, 'extrovert' => 0],
                    'risk' => ['low' => 0, 'medium' => 10, 'high' => 5],
                    'skills' => ['writing' => 25, 'marketing' => 5],
                    'assets' => []
                ]
            ],
            [
                'title' => 'Drone Photography & Real Estate',
                'why_fits' => 'Capitalizes on your high-value equipment for premium payouts.',
                'earning_potential' => '$1000-4000/month',
                'difficulty' => 'Medium',
                'time_commitment' => '4-10 hours/week',
                'deep_explain' => "Real estate agents constantly need aerial shots for premium listings.\n\nStep 1: Get your Part 107 Drone License.\nStep 2: Build a portfolio by shooting your own neighborhood.\nStep 3: Cold email or walk into local real estate offices.\nStep 4: Charge $150-$300 per house shoot (takes 1 hour).\nStep 5: Upsell with video walkthroughs.",
                'weights' => [
                    'work_pref' => ['remote' => -10, 'hybrid' => 5, 'in_person' => 10],
                    'personality' => ['introvert' => 0, 'ambivert' => 5, 'extrovert' => 10],
                    'risk' => ['low' => 0, 'medium' => 5, 'high' => 10],
                    'skills' => ['photography' => 15, 'video' => 10, 'sales' => 10],
                    'assets' => ['drone' => 50, 'car' => 5]
                ]
            ],
            [
                'title' => 'Rideshare / Delivery Driver',
                'why_fits' => 'Immediate, guaranteed income whenever you want to work.',
                'earning_potential' => '$400-2000/month',
                'difficulty' => 'Easy',
                'time_commitment' => '5-20 hours/week',
                'deep_explain' => "The most flexible way to turn your car into cash.\n\nOption 1 - Rideshare: Uber/Lyft\nOption 2 - Food delivery: DoorDash/UberEats\n\nMaximize earnings by driving during surge pricing (weekend nights, rain) and multi-apping to reduce downtime. Remember to track your mileage for tax deductions!",
                'weights' => [
                    'work_pref' => ['remote' => -20, 'hybrid' => 0, 'in_person' => 10],
                    'personality' => ['introvert' => 5, 'ambivert' => 5, 'extrovert' => 10], // Uber needs extrovert, Doordash introvert
                    'risk' => ['low' => 10, 'medium' => 0, 'high' => 0],
                    'skills' => [],
                    'assets' => ['car' => 40, 'truck' => 20]
                ]
            ],
            [
                'title' => 'Local Handyman / TaskRabbit',
                'why_fits' => 'High hourly rates for practical, in-person skills.',
                'earning_potential' => '$800-3000/month',
                'difficulty' => 'Medium',
                'time_commitment' => '5-15 hours/week',
                'deep_explain' => "People lack the time or tools for basic home maintenance.\n\nStep 1: Sign up on TaskRabbit or Thumbtack.\nStep 2: List services like TV mounting, furniture assembly, or minor repairs.\nStep 3: Set rates at $40-$80/hour depending on your city.\nStep 4: Do excellent work and leave a business card.\nStep 5: Transition off-app for repeat clients.",
                'weights' => [
                    'work_pref' => ['remote' => -20, 'hybrid' => -10, 'in_person' => 20],
                    'personality' => ['introvert' => 0, 'ambivert' => 10, 'extrovert' => 10],
                    'risk' => ['low' => 5, 'medium' => 10, 'high' => 0],
                    'skills' => ['handyman' => 30],
                    'assets' => ['power_tools' => 25, 'truck' => 15, 'car' => 5]
                ]
            ],
            [
                'title' => 'Social Media Manager',
                'why_fits' => 'Creative, remote work with recurring monthly retainers.',
                'earning_potential' => '$1000-5000/month',
                'difficulty' => 'Medium',
                'time_commitment' => '10-20 hours/week',
                'deep_explain' => "Local businesses know they need social media, but don't have time.\n\nStep 1: Create a mock content calendar for a local coffee shop.\nStep 2: Walk in or DM them offering a 1-month free trial.\nStep 3: Use tools like Canva to design posts and Buffer to schedule.\nStep 4: Once you prove value, charge $300-$1000/mo per client.\nStep 5: Get 5 clients to hit a full-time income.",
                'weights' => [
                    'work_pref' => ['remote' => 10, 'hybrid' => 10, 'in_person' => -5],
                    'personality' => ['introvert' => 0, 'ambivert' => 10, 'extrovert' => 10],
                    'risk' => ['low' => 0, 'medium' => 10, 'high' => 10],
                    'skills' => ['marketing' => 20, 'design' => 15, 'video' => 10],
                    'assets' => ['gaming_pc' => 5]
                ]
            ],
            [
                'title' => 'Freelance Web Developer',
                'why_fits' => 'High technical barrier means premium pay and low competition.',
                'earning_potential' => '$1500-6000/month',
                'difficulty' => 'Hard',
                'time_commitment' => '10-25 hours/week',
                'deep_explain' => "Every business needs a website or web application.\n\nStep 1: Build a stunning personal portfolio.\nStep 2: Start on Upwork looking for 'bug fixes' or 'PSD to HTML' to build reviews.\nStep 3: Reach out to local businesses with outdated websites.\nStep 4: Charge $1k-$5k per custom site.\nStep 5: Offer monthly maintenance retainers ($100/mo).",
                'weights' => [
                    'work_pref' => ['remote' => 15, 'hybrid' => 5, 'in_person' => -10],
                    'personality' => ['introvert' => 10, 'ambivert' => 5, 'extrovert' => 0],
                    'risk' => ['low' => 0, 'medium' => 5, 'high' => 10],
                    'skills' => ['coding' => 30, 'design' => 5],
                    'assets' => ['gaming_pc' => 10]
                ]
            ],
            [
                'title' => 'Online Language Tutor',
                'why_fits' => 'Leverages your language skills for global remote income.',
                'earning_potential' => '$500-2000/month',
                'difficulty' => 'Easy',
                'time_commitment' => '5-15 hours/week',
                'deep_explain' => "People worldwide want to learn your native or secondary language.\n\nStep 1: Apply to platforms like iTalki, Preply, or VIPKid.\nStep 2: Create an engaging intro video.\nStep 3: Open your calendar for conversational practice or structured lessons.\nStep 4: Start at $15/hr to get reviews.\nStep 5: Increase to $25-$40/hr once established.",
                'weights' => [
                    'work_pref' => ['remote' => 15, 'hybrid' => 5, 'in_person' => 0],
                    'personality' => ['introvert' => -5, 'ambivert' => 10, 'extrovert' => 15],
                    'risk' => ['low' => 10, 'medium' => 5, 'high' => 0],
                    'skills' => ['translation' => 20, 'teaching' => 20],
                    'assets' => ['camera' => 5]
                ]
            ],
            [
                'title' => 'Airbnb / Room Rental',
                'why_fits' => 'Pure passive income using an asset you already own.',
                'earning_potential' => '$500-1500/month',
                'difficulty' => 'Easy',
                'time_commitment' => '2-5 hours/week',
                'deep_explain' => "Your spare room is leaving money on the table.\n\nStep 1: Furnish the room nicely (IKEA is fine, focus on comfort).\nStep 2: Take bright, wide-angle photos.\nStep 3: List on Airbnb.\nStep 4: Automate check-in with a keypad lock.\nStep 5: Hire a cleaner to handle turnovers.",
                'weights' => [
                    'work_pref' => ['remote' => 0, 'hybrid' => 0, 'in_person' => 10],
                    'personality' => ['introvert' => 5, 'ambivert' => 10, 'extrovert' => 10],
                    'risk' => ['low' => 0, 'medium' => 5, 'high' => 10],
                    'skills' => [],
                    'assets' => ['spare_room' => 50]
                ]
            ],
            [
                'title' => 'High-Ticket Sales Closer',
                'why_fits' => 'Massive earning potential for outgoing personalities.',
                'earning_potential' => '$3000-10000/month',
                'difficulty' => 'Hard',
                'time_commitment' => '15-30 hours/week',
                'deep_explain' => "Course creators and coaches need people to close warm leads over the phone.\n\nStep 1: Learn consultative selling.\nStep 2: Find influencers or businesses selling $2k+ programs.\nStep 3: Offer to take their sales calls on straight commission (usually 10-20%).\nStep 4: Take 5 calls a day.\nStep 5: Close 1 deal a day at a $5k price = $500/day for you.",
                'weights' => [
                    'work_pref' => ['remote' => 10, 'hybrid' => 10, 'in_person' => 0],
                    'personality' => ['introvert' => -10, 'ambivert' => 5, 'extrovert' => 20],
                    'risk' => ['low' => -10, 'medium' => 0, 'high' => 20],
                    'skills' => ['sales' => 30],
                    'assets' => []
                ]
            ],
            [
                'title' => 'YouTube Video Editor',
                'why_fits' => 'High demand skill that lets you work completely alone.',
                'earning_potential' => '$1000-4000/month',
                'difficulty' => 'Medium',
                'time_commitment' => '10-20 hours/week',
                'deep_explain' => "YouTubers are drowning in footage and desperately need editors.\n\nStep 1: Learn Premiere Pro or DaVinci Resolve.\nStep 2: Download Twitch streams or podcasts and edit them into engaging shorts/TikToks.\nStep 3: DM creators your edits of their own content.\nStep 4: Charge $50-$150 per video.\nStep 5: Build a roster of 3-4 recurring clients.",
                'weights' => [
                    'work_pref' => ['remote' => 15, 'hybrid' => 5, 'in_person' => -10],
                    'personality' => ['introvert' => 15, 'ambivert' => 5, 'extrovert' => -5],
                    'risk' => ['low' => 0, 'medium' => 10, 'high' => 5],
                    'skills' => ['video' => 30],
                    'assets' => ['gaming_pc' => 20]
                ]
            ]
        ];

        // Scoring Engine
        foreach ($hustles as &$hustle) {
            $score = 0;
            $w = $hustle['weights'];

            // 1. Work Preference
            $score += $w['work_pref'][$workPreference] ?? 0;

            // 2. Personality
            $score += $w['personality'][$personality] ?? 0;

            // 3. Risk
            $score += $w['risk'][$riskTolerance] ?? 0;

            // 4. Skills (Huge bonus for matching skills)
            foreach ($skills as $skill) {
                if (isset($w['skills'][$skill])) {
                    $score += $w['skills'][$skill];
                }
            }

            // 5. Assets (Huge bonus for matching assets)
            foreach ($assets as $asset) {
                if (isset($w['assets'][$asset])) {
                    $score += $w['assets'][$asset];
                }
            }

            $hustle['score'] = $score;
        }

        // Sort by score descending
        usort($hustles, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Return top 3
        return array_slice($hustles, 0, 3);
    }
}