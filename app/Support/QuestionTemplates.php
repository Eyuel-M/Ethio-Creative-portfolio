<?php

namespace App\Support;

/** Recommended screening questions by creative category. Clients may edit, remove or add. */
class QuestionTemplates
{
    public static function common(): array
    {
        return [
            ['type' => 'number', 'prompt' => 'How many years of relevant experience do you have?', 'is_required' => true, 'options' => []],
            ['type' => 'yes_no', 'prompt' => 'Can you meet the stated deadline?', 'is_required' => true, 'options' => []],
            ['type' => 'text', 'prompt' => 'Briefly describe your approach to this project.', 'is_required' => true, 'options' => []],
        ];
    }

    public static function forCategory(?string $slug): array
    {
        $specific = match ($slug) {
            'graphic-design', 'brand-identity' => [
                ['type' => 'multi_select', 'prompt' => 'Which design tools do you use?', 'is_required' => true, 'options' => ['Adobe Illustrator', 'Adobe Photoshop', 'Figma', 'Affinity Designer', 'CorelDRAW']],
                ['type' => 'portfolio_link', 'prompt' => 'Link to a similar identity project.', 'is_required' => false, 'options' => []],
            ],
            'ui-ux-design', 'web-design' => [
                ['type' => 'multi_select', 'prompt' => 'Which tools do you use?', 'is_required' => true, 'options' => ['Figma', 'Framer', 'Webflow', 'Adobe XD', 'Sketch']],
                ['type' => 'yes_no', 'prompt' => 'Have you designed a responsive website before?', 'is_required' => true, 'options' => []],
            ],
            'video-animation' => [
                ['type' => 'multiple_choice', 'prompt' => 'Primary editing software?', 'is_required' => true, 'options' => ['Premiere Pro', 'DaVinci Resolve', 'Final Cut Pro', 'After Effects']],
                ['type' => 'portfolio_link', 'prompt' => 'Link to a showreel.', 'is_required' => true, 'options' => []],
            ],
            'photography' => [
                ['type' => 'yes_no', 'prompt' => 'Do you own professional lighting equipment?', 'is_required' => false, 'options' => []],
                ['type' => 'multiple_choice', 'prompt' => 'Preferred delivery format?', 'is_required' => false, 'options' => ['RAW + edited JPEG', 'Edited JPEG only', 'TIFF']],
            ],
            default => [],
        };

        return array_merge(self::common(), $specific);
    }
}
