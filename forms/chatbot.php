<?php

declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim((string) $input['message']) : '';

if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No message provided']);
    exit;
}

$lower = strtolower($message);

$responses = [
    'decision' => 'Our AI-Powered Decision Intelligence Engine helps leaders navigate complexity with greater clarity. We connect Generative AI, organisational knowledge, data insights, and proven decision frameworks to enable faster, smarter, and more transparent decision-making.',
    'intelligence' => 'Our AI-Powered Decision Intelligence Engine helps leaders navigate complexity with greater clarity. We connect Generative AI, organisational knowledge, data insights, and proven decision frameworks to enable faster, smarter, and more transparent decision-making.',
    'talent' => 'Through IT Talent Introduction, we connect organisations with technology professionals who can accelerate innovation, transformation, and growth. We focus on matching the right expertise with the right opportunities.',
    'training' => 'Our IT Training & AI Enablement programs empower people and organisations with the knowledge and skills needed for an AI-driven future through practical training, technology education, and AI adoption programs.',
    'enablement' => 'Our IT Training & AI Enablement programs empower people and organisations with the knowledge and skills needed for an AI-driven future through practical training, technology education, and AI adoption programs.',
    'ai' => 'RGL Business Solutions is an AI technology company. We combine Artificial Intelligence, business knowledge, and technology expertise to create practical solutions that augment human capability and transform how organisations operate.',
    'services' => 'RGL offers three focus areas: AI-Powered Decision Intelligence Engine, IT Talent Introduction, and IT Training & AI Enablement. Together, they help organisations transform, innovate, and grow.',
    'contact' => 'You can reach us at info@rgl.com.ph or +63 906 967 3630. We are based in Makati City, Metro Manila, Philippines.',
    'about' => 'RGL Business Solutions is an AI technology company building intelligent solutions that help organisations work smarter, make better decisions, and unlock new possibilities. We believe the future belongs to organisations that combine human intelligence with the power of AI.',
    'default' => 'Thanks for reaching out to RGL Business Solutions. We empower organisations with intelligent AI solutions, top IT talent, and future-ready skills. Ask about our Decision Intelligence Engine, IT Talent Introduction, IT Training & AI Enablement, or how to contact us.',
];

$response = $responses['default'];

foreach ($responses as $keyword => $text) {
    if ($keyword !== 'default' && str_contains($lower, $keyword)) {
        $response = $text;
        break;
    }
}

echo json_encode(['response' => $response]);
