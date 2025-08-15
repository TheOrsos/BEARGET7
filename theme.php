<?php
session_start();
header("Content-type: text/css");

// Tema di default se non impostato
$theme = $_SESSION['theme'] ?? 'dark-indigo';

// Definisci le palette di colori per ogni tema
$palettes = [
    'dark-indigo' => [
        '500' => '#6366f1', '600' => '#4f46e5', '700' => '#4338ca',
        'gray-100' => '#f3f4f6', 'gray-200' => '#e5e7eb', 'gray-300' => '#d1d5db', 'gray-400' => '#9ca3af',
        'gray-700' => '#374151', 'gray-800' => '#1f2937', 'gray-900' => '#111827',
    ],
    'forest-green' => [
        '500' => '#22c55e', '600' => '#16a34a', '700' => '#15803d',
        'gray-100' => '#f3f4f6', 'gray-200' => '#e5e7eb', 'gray-300' => '#d1d5db', 'gray-400' => '#9ca3af',
        'gray-700' => '#3f3f46', 'gray-800' => '#27272a', 'gray-900' => '#18181b',
    ],
    'ocean-blue' => [
        '500' => '#3b82f6', '600' => '#2563eb', '700' => '#1d4ed8',
        'gray-100' => '#f3f4f6', 'gray-200' => '#e5e7eb', 'gray-300' => '#d1d5db', 'gray-400' => '#9ca3af',
        'gray-700' => '#374151', 'gray-800' => '#1f2937', 'gray-900' => '#111827',
    ],
    'sunset-orange' => [
        '500' => '#f97316', '600' => '#ea580c', '700' => '#c2410c',
        'gray-100' => '#f3f4f6', 'gray-200' => '#e5e7eb', 'gray-300' => '#d1d5db', 'gray-400' => '#9ca3af',
        'gray-700' => '#44403c', 'gray-800' => '#292524', 'gray-900' => '#1c1917',
    ],
    'royal-purple' => [
        '500' => '#a855f7', '600' => '#9333ea', '700' => '#7e22ce',
        'gray-100' => '#f3f4f6', 'gray-200' => '#e5e7eb', 'gray-300' => '#d1d5db', 'gray-400' => '#9ca3af',
        'gray-700' => '#3730a3', 'gray-800' => '#312e81', 'gray-900' => '#1e1b4b',
    ],
    'graphite-gray' => [
        '500' => '#6b7280', '600' => '#4b5563', '700' => '#374151',
        'gray-100' => '#f3f4f6', 'gray-200' => '#e5e7eb', 'gray-300' => '#d1d5db', 'gray-400' => '#9ca3af',
        'gray-700' => '#374151', 'gray-800' => '#1f2937', 'gray-900' => '#111827',
    ]
];

$current_palette = $palettes[$theme] ?? $palettes['dark-indigo'];
?>

:root {
    --color-primary-500: <?php echo $current_palette['500']; ?>;
    --color-primary-600: <?php echo $current_palette['600']; ?>;
    --color-primary-700: <?php echo $current_palette['700']; ?>;

    --color-gray-100: <?php echo $current_palette['gray-100']; ?>;
    --color-gray-200: <?php echo $current_palette['gray-200']; ?>;
    --color-gray-300: <?php echo $current_palette['gray-300']; ?>;
    --color-gray-400: <?php echo $current_palette['gray-400']; ?>;
    --color-gray-700: <?php echo $current_palette['gray-700']; ?>;
    --color-gray-800: <?php echo $current_palette['gray-800']; ?>;
    --color-gray-900: <?php echo $current_palette['gray-900']; ?>;

    --color-success: #22c55e; /* Green */
    --color-danger: #ef4444; /* Red */
    --color-warning: #f59e0b; /* Amber */
}