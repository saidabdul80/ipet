<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppSettingService;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    protected $appSettingService;

    public function __construct(AppSettingService $appSettingService)
    {
        $this->appSettingService = $appSettingService;
    }

    /**
     * Get app settings (public - no auth required)
     */
    public function index()
    {
        return response()->json($this->appSettingService->getSettings());
    }

    /**
     * Update app settings (admin only)
     */
    public function update(Request $request)
    {
        // Check if user is super admin
        if (!$request->user() || !$request->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_short_name' => 'nullable|string|max:50',
            'app_description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'logo_dark' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:512',
            'login_background' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'login_side_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'error_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'info_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'success_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'warning_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'login_layout' => 'nullable|in:centered,split,background',
            'login_title' => 'nullable|string|max:255',
            'login_subtitle' => 'nullable|string',
            'show_logo_on_login' => 'nullable|boolean',
            'currency_symbol' => 'nullable|string|max:10',
            'currency_code' => 'nullable|string|max:3',
            'timezone' => 'nullable|string|max:50',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string',
            'company_website' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
        ]);

        $settings = $this->appSettingService->updateSettings($validated);

        return response()->json([
            'message' => 'Settings updated successfully',
            'data' => $settings,
        ]);
    }

    /**
     * Delete a specific image
     */
    public function deleteImage(Request $request, string $type)
    {
        // Check if user is super admin
        if (!$request->user() || !$request->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $settings = $this->appSettingService->deleteImage($type);

            return response()->json([
                'message' => 'Image deleted successfully',
                'data' => $settings,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Reset to default settings
     */
    public function reset(Request $request)
    {
        // Check if user is super admin
        if (!$request->user() || !$request->user()->hasRole('Super Admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $settings = $this->appSettingService->resetToDefaults();

        return response()->json([
            'message' => 'Settings reset to defaults successfully',
            'data' => $settings,
        ]);
    }
}

