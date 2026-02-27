<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display settings management page
     */
    public function index(): Response
    {
        $settings = SystemSetting::with('updatedBy:id,first_name,last_name')
            ->orderBy('category')
            ->orderBy('display_order')
            ->get()
            ->groupBy('category');

        $categories = [
            SystemSetting::CATEGORY_POLICY_RISK => [
                'name' => 'Policy & Risk Management',
                'icon' => 'shield',
                'description' => 'Loan policy thresholds, risk parameters, and eligibility rules',
            ],
            SystemSetting::CATEGORY_INTEREST_RATES => [
                'name' => 'Interest Rates & Fees',
                'icon' => 'percentage',
                'description' => 'Interest rates, calculation methods, and fee structures',
            ],
            SystemSetting::CATEGORY_ASSESSMENT => [
                'name' => 'Assessment Parameters',
                'icon' => 'calculator',
                'description' => 'Bank statement analysis, income validation, and affordability rules',
            ],
            SystemSetting::CATEGORY_WORKFLOW => [
                'name' => 'Approval Workflow',
                'icon' => 'workflow',
                'description' => 'Approval limits, maker-checker settings, and override permissions',
            ],
            SystemSetting::CATEGORY_DOCUMENTS => [
                'name' => 'Document Requirements',
                'icon' => 'file',
                'description' => 'Required documents, validity periods, and submission rules',
            ],
            SystemSetting::CATEGORY_EMAILS => [
                'name' => 'Email Templates',
                'icon' => 'mail',
                'description' => 'Notification templates and email configurations',
            ],
            SystemSetting::CATEGORY_BRANDING => [
                'name' => 'Branding & Appearance',
                'icon' => 'palette',
                'description' => 'Logos, colors, themes, and report headers',
            ],
            SystemSetting::CATEGORY_SYSTEM => [
                'name' => 'System Configuration',
                'icon' => 'settings',
                'description' => 'General system settings, formats, and defaults',
            ],
            SystemSetting::CATEGORY_INTEGRATIONS => [
                'name' => 'Integrations',
                'icon' => 'link',
                'description' => 'API keys, payment gateways, and third-party services',
            ],
            SystemSetting::CATEGORY_COMPLIANCE => [
                'name' => 'Compliance & Audit',
                'icon' => 'check-circle',
                'description' => 'Data retention, audit logs, and regulatory compliance',
            ],
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'categories' => $categories,
        ]);
    }

    /**
     * Update a specific setting
     */
    public function update(Request $request, SystemSetting $setting)
    {
        if (!$setting->is_editable) {
            return back()->withErrors(['message' => 'This setting cannot be modified.']);
        }

        // Validate based on data_type and validation_rules
        $rules = $this->getValidationRules($setting);
        $validated = $request->validate(['value' => $rules]);

        $setting->update([
            'value' => is_array($validated['value']) ? json_encode($validated['value']) : $validated['value'],
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Setting updated successfully.');
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:system_settings,id',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $settingData) {
            $setting = SystemSetting::find($settingData['id']);
            
            if ($setting && $setting->is_editable) {
                $setting->update([
                    'value' => is_array($settingData['value']) ? json_encode($settingData['value']) : $settingData['value'],
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        SystemSetting::clearCache();

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Reset a setting to its default value
     */
    public function reset(SystemSetting $setting)
    {
        if (!$setting->is_editable) {
            return back()->withErrors(['message' => 'This setting cannot be reset.']);
        }

        $setting->update([
            'value' => $setting->default_value,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Setting reset to default value.');
    }

    /**
     * Reset all settings in a category to defaults
     */
    public function resetCategory(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
        ]);

        SystemSetting::where('category', $validated['category'])
            ->where('is_editable', true)
            ->each(function ($setting) {
                $setting->update([
                    'value' => $setting->default_value,
                    'updated_by' => auth()->id(),
                ]);
            });

        SystemSetting::clearCache();

        return back()->with('success', 'Category settings reset to defaults.');
    }

    /**
     * Get public settings (for frontend/API)
     */
    public function getPublic()
    {
        return response()->json(SystemSetting::getPublic());
    }

    /**
     * Clear settings cache
     */
    public function clearCache()
    {
        SystemSetting::clearCache();
        return back()->with('success', 'Settings cache cleared.');
    }

    /**
     * Export settings as JSON
     */
    public function export()
    {
        $settings = SystemSetting::all();
        
        return response()->json($settings, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="system_settings_' . now()->format('Y-m-d_H-i-s') . '.json"',
        ]);
    }

    /**
     * Import settings from JSON
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $settings = json_decode($content, true);

        if (!$settings) {
            return back()->withErrors(['message' => 'Invalid JSON file.']);
        }

        foreach ($settings as $settingData) {
            $setting = SystemSetting::where('key', $settingData['key'])->first();
            
            if ($setting && $setting->is_editable) {
                $setting->update([
                    'value' => $settingData['value'],
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        SystemSetting::clearCache();

        return back()->with('success', 'Settings imported successfully.');
    }

    /**
     * Get validation rules for a setting
     */
    private function getValidationRules(SystemSetting $setting): array
    {
        $rules = [];

        switch ($setting->data_type) {
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'email':
                $rules[] = 'email';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'json':
                $rules[] = 'json';
                break;
            default:
                $rules[] = 'string';
        }

        // Add custom validation rules from the setting
        if ($setting->validation_rules) {
            foreach ($setting->validation_rules as $key => $value) {
                if ($key === 'min') {
                    $rules[] = "min:{$value}";
                } elseif ($key === 'max') {
                    $rules[] = "max:{$value}";
                } elseif ($key === 'regex') {
                    $rules[] = "regex:{$value}";
                } elseif ($key === 'in') {
                    $rules[] = 'in:' . implode(',', $value);
                }
            }
        }

        return $rules;
    }
}
