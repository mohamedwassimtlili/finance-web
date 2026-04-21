<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\Suggestion;
use App\Service\HuggingFaceService;
use App\Service\RulesBasedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/finance/side-hustle')]
class SideHustleController extends AbstractController
{
    #[Route('/', name: 'side_hustle_index')]
    public function index(): Response
    {
        return $this->render('finance/side_hustle/index.html.twig');
    }

    #[Route('/generate', name: 'side_hustle_generate', methods: ['POST'])]
    public function generate(
        Request $request,
        HuggingFaceService $huggingface,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NotifierInterface $notifier
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Please login first');
            return $this->redirectToRoute('app_login');
        }

        $description = $request->get('user_description');
        
        if (empty($description)) {
            $this->addFlash('error', 'Please describe yourself first.');
            return $this->redirectToRoute('side_hustle_index');
        }

        return $this->processGeneration($description, $user, $huggingface, $em, $validator, $notifier);
    }

    #[Route('/generate-quick', name: 'side_hustle_generate_quick', methods: ['POST'])]
    public function generateQuick(
        Request $request,
        HuggingFaceService $huggingface,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NotifierInterface $notifier
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Please login first');
            return $this->redirectToRoute('app_login');
        }

        // Build description from form options
        $job = $request->get('job');
        $hobbies = $request->get('hobbies', []);
        $assets = $request->get('assets');
        $goal = $request->get('goal');
        
        $jobLabels = [
            'teacher' => 'teacher',
            'nurse' => 'nurse', 
            'student' => 'college student',
            'office' => 'office worker',
            'retail' => 'retail worker',
            'it' => 'IT professional',
            'creative' => 'creative person',
            'retired' => 'retired person',
            'other' => 'professional'
        ];
        
        $jobText = $jobLabels[$job] ?? 'professional';
        $hobbiesText = !empty($hobbies) ? ' who enjoys ' . implode(', ', $hobbies) : '';
        $assetsText = $assets ? ' I own a ' . $assets . '.' : '';
        
        $description = "I'm a {$jobText}{$hobbiesText}.{$assetsText} I want to earn {$goal} per month.";
        
        return $this->processGeneration($description, $user, $huggingface, $em, $validator, $notifier);
    }

#[Route('/generate-rules', name: 'side_hustle_generate_rules', methods: ['POST'])]
public function generateRules(
    Request $request,
    RulesBasedService $rulesBased,
    EntityManagerInterface $em,
    ValidatorInterface $validator,
    NotifierInterface $notifier
): Response {
    $user = $this->getUser();
    
    if (!$user) {
        $this->addFlash('error', 'Please login first');
        return $this->redirectToRoute('app_login');
    }

    // Collect form data
    $formData = [
        'work_preference' => $request->get('work_preference'),
        'personality' => $request->get('personality'),
        'motivation' => $request->get('motivation'),
        'risk_tolerance' => $request->get('risk_tolerance'),
        'goal' => $request->get('goal'),
        'skills' => $request->get('skills', []),
        'assets' => $request->get('assets', []),
    ];
    
    // Handle skills (could be array or string)
    $skillsStr = '';
    if (isset($formData['skills'])) {
        if (is_array($formData['skills'])) {
            $skillsStr = implode(', ', $formData['skills']);
        } else {
            $skillsStr = $formData['skills'];
        }
    }
    
    // Handle assets (could be array or string)
    $assetsStr = '';
    if (isset($formData['assets'])) {
        if (is_array($formData['assets'])) {
            $assetsStr = implode(', ', $formData['assets']);
        } else {
            $assetsStr = $formData['assets'];
        }
    }
    
    // Build description for display
    $description = "Location: " . ($formData['work_preference'] ?? 'not specified') . 
                   " | Personality: " . ($formData['personality'] ?? 'not specified') . 
                   " | Motivation: " . ($formData['motivation'] ?? 'not specified') . 
                   " | Risk: " . ($formData['risk_tolerance'] ?? 'not specified') . 
                   "\nSkills: " . ($skillsStr ?: 'None selected') . 
                   "\nAssets: " . ($assetsStr ?: 'None selected') .
                   "\nGoal: " . ($formData['goal'] ?? 'not specified') . "/month";
    
    // Create Profile
    $profile = new Profile();
    $profile->setUser($user);
    $profile->setDescription($description);
    $profile->setStatus('active');

    $em->persist($profile);
    $em->flush();

    // Generate suggestions using RULES (NO API)
    $suggestions = $rulesBased->generateSuggestions($formData);

    // Save suggestions
    foreach ($suggestions as $suggestionData) {
        $suggestion = new Suggestion();
        $suggestion->setUser($user);
        $suggestion->setProfile($profile);
        $suggestion->setTitle($suggestionData['title']);
        $suggestion->setData($suggestionData);
        $suggestion->setScript($suggestionData['deep_explain']);
        $suggestion->setListened(false);
        $suggestion->setStarted(false);
        
        $em->persist($suggestion);
    }
    
    $em->flush();
    

    return $this->redirectToRoute('side_hustle_results', ['id' => $profile->getId()]);
}

    private function processGeneration(
        string $description,
        $user,
        HuggingFaceService $huggingface,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NotifierInterface $notifier
    ): Response {
        // Create Profile
        $profile = new Profile();
        $profile->setUser($user);
        $profile->setDescription($description);
        $profile->setStatus('active');

        $errors = $validator->validate($profile);
        if (count($errors) > 0) {
            $this->addFlash('error', 'Invalid description. Please provide at least 10 characters.');
            return $this->redirectToRoute('side_hustle_index');
        }

        $em->persist($profile);
        $em->flush();

        // Call HuggingFace API
        try {
            $suggestions = $huggingface->generateSideHustles($description);
            
            if (empty($suggestions)) {
                throw new \Exception('No suggestions returned from API');
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'API Error: ' . $e->getMessage());
            return $this->redirectToRoute('side_hustle_index');
        }

        // Save suggestions to database
        foreach ($suggestions as $suggestionData) {
            $suggestion = new Suggestion();
            $suggestion->setUser($user);
            $suggestion->setProfile($profile);
            $suggestion->setTitle($suggestionData['title'] ?? 'Side Hustle');
            $suggestion->setData($suggestionData);
            $suggestion->setScript($suggestionData['deep_explain'] ?? 'No script available');
            $suggestion->setListened(false);
            $suggestion->setStarted(false);
            
            $em->persist($suggestion);
        }
        
        $em->flush();
        

        return $this->redirectToRoute('side_hustle_results', ['id' => $profile->getId()]);
    }

    #[Route('/results/{id}', name: 'side_hustle_results')]
    public function results(Profile $profile): Response
    {
        $user = $this->getUser();
        
        if (!$user || $profile->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Access denied');
        }

        $suggestions = $profile->getSuggestions();
        
        return $this->render('finance/side_hustle/results.html.twig', [
            'profile' => $profile,
            'suggestions' => $suggestions
        ]);
    }

    #[Route('/listen/{id}', name: 'side_hustle_listen', methods: ['POST'])]
    public function listen(Suggestion $suggestion, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user || $suggestion->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $suggestion->setListened(true);
        $em->flush();

        return $this->json(['success' => true]);
    }
    
#[Route('/start/{id}', name: 'side_hustle_start', methods: ['POST'])]
public function start($id, EntityManagerInterface $em): Response
{
    // Simple test - return success without any database operation
    return $this->json(['success' => true, 'id' => $id]);
}

    
}