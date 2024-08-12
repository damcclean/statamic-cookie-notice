<?php

namespace DuncanMcClean\CookieNotice\Scripts;

use DuncanMcClean\CookieNotice\Rules\ValidGtmContainerID;
use DuncanMcClean\CookieNotice\Rules\ValidInlineJavaScript;
use DuncanMcClean\CookieNotice\Rules\ValidMetaPixelID;
use Statamic\Fields\Blueprint as StatamicBlueprint;

class Blueprint
{
    public static function blueprint(): StatamicBlueprint
    {
        return app(StatamicBlueprint::class)->setContents([
            'tabs' => [
                'main' => [
                    'sections' => collect([
                        [
                            'fields' => [
                                [
                                    'handle' => 'revision',
                                    'field' => [
                                        'type' => 'text',
                                        'display' => __('Revision'),
                                        'instructions' => __('When you add or update scripts, you should increment this number so users are prompted to re-consent.'),
                                        'validate' => ['required'],
                                        'default' => '1',
                                    ],
                                ],
                            ],
                        ],
                    ])->merge(collect(config('cookie-notice.consent_groups'))->map(function (array $consentGroup) {
                        $fields = [
                            [
                                'handle' => "{$consentGroup['handle']}",
                                'field' => [
                                    'fields' => [
                                        [
                                            'handle' => 'script_type',
                                            'field' => [
                                                'options' => [
                                                    'google-tag-manager' => __('Google Tag Manager'),
                                                    'meta-pixel' => __('Meta Pixel'),
                                                    'other' => __('Other (JavaScript)'),
                                                ],
                                                'type' => 'button_group',
                                                'display' => __('Script Type'),
                                                'width' => 50,
                                                'required' => true,
                                            ],
                                        ],
                                        [
                                            'handle' => 'spacer',
                                            'field' => ['type' => 'spacer', 'width' => 50],
                                        ],
                                        [
                                            'handle' => 'gtm_container_id',
                                            'field' => [
                                                'type' => 'text',
                                                'display' => __('Container ID'),
                                                'instructions' => __('You can find this at the top right of your Google Tag Manager account. Usually starts with `GTM-`.'),
                                                'if' => ['script_type' => 'equals google-tag-manager'],
                                                'validate' => [
                                                    'required_if:{this}.script_type,google-tag-manager',
                                                    new ValidGtmContainerID,
                                                ],
                                            ],
                                        ],
                                        [
                                            'handle' => 'consent_grants',
                                            'field' => [
                                                'type' => 'checkboxes',
                                                'display' => __('Default grants consent'),
                                                'instructions' => __('What consent needs to default be granted when accepting this group.'),
                                                'options' => [
                                                    'ad_storage' => 'ad_storage',
                                                    'ad_user_data' => 'ad_user_data',
                                                    'ad_personalization' => 'ad_personalization',
                                                    'analytics_storage' => 'analytics_storage'
                                                ],
                                                'inline' => true,
                                                'default' => ['ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage'],
                                                'if' => ['script_type' => 'equals google-tag-manager'],
                                                'validate' => [
                                                    'array',
                                                ],
                                            ],
                                        ],
                                        [
                                            'handle' => 'meta_pixel_id',
                                            'field' => [
                                                'type' => 'text',
                                                'display' => __('Pixel ID'),
                                                'instructions' => __('You can find this in your Meta Events Manager account.'),
                                                'if' => ['script_type' => 'equals meta-pixel'],
                                                'validate' => [
                                                    'required_if:{this}.script_type,meta-pixel',
                                                    new ValidMetaPixelID,
                                                ],
                                            ],
                                        ],
                                        [
                                            'handle' => 'inline_javascript',
                                            'field' => [
                                                'type' => 'code',
                                                'display' => __('Inline JavaScript'),
                                                'instructions' => __('Please remove the `<script>` and `</script>` tags.'),
                                                'mode' => 'javascript',
                                                'mode_selectable' => false,
                                                'if' => ['script_type' => 'equals other'],
                                                'validate' => [
                                                    'required_if:{this}.script_type,other',
                                                    new ValidInlineJavaScript,
                                                ],
                                            ],
                                        ],
                                    ],
                                    'mode' => 'stacked',
                                    'type' => 'grid',
                                    'display' => 'Scripts',
                                    'hide_display' => true,
                                    'add_row' => 'Add Script',
                                    'fullscreen' => false,
                                ],
                            ],
                        ];

                        return [
                            'display' => $consentGroup['name'],
                            'instructions' => __("Configure the scripts to be loaded after consent is provided for the **{$consentGroup['name']}** group."),
                            'fields' => $fields,
                        ];
                    })->all())->all(),
                ],
            ],
        ]);
    }
}
