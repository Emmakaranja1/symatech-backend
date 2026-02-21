<?php

namespace App\Http\Controllers\Redis;

use App\Http\Controllers\Controller;
use App\Services\Redis\UserPreferencesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserPreferencesController extends Controller
{
    protected $preferencesService;

    public function __construct(UserPreferencesService $preferencesService)
    {
        $this->preferencesService = $preferencesService;
    }

    public function setPreference(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'key' => 'required|string|max:255',
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $key = $request->input('key');
        $value = $request->input('value');

        $success = $this->preferencesService->setPreference($userId, $key, $value);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Preference set successfully',
                'data' => [
                    'user_id' => $userId,
                    'key' => $key,
                    'value' => $value
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to set preference'
        ], 500);
    }

    public function getPreference(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'key' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $key = $request->input('key');
        $default = $request->input('default');

        $value = $this->preferencesService->getPreference($userId, $key, $default);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'key' => $key,
                'value' => $value,
                'found' => $value !== $default
            ]
        ]);
    }

    public function getAllPreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $preferences = $this->preferencesService->getAllPreferences($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'preferences' => $preferences,
                'count' => count($preferences)
            ]
        ]);
    }

    public function removePreference(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'key' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $key = $request->input('key');

        $success = $this->preferencesService->removePreference($userId, $key);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Preference removed successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to remove preference or preference not found'
        ], 404);
    }

    public function clearAllPreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $success = $this->preferencesService->clearAllPreferences($userId);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'All preferences cleared successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to clear preferences or no preferences found'
        ], 404);
    }

    public function setMultiplePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'preferences' => 'required|array',
            'preferences.*.key' => 'required|string|max:255',
            'preferences.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $preferencesData = $request->input('preferences');
        
        $preferences = [];
        foreach ($preferencesData as $pref) {
            $preferences[$pref['key']] = $pref['value'];
        }

        $success = $this->preferencesService->setMultiplePreferences($userId, $preferences);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Multiple preferences set successfully',
                'data' => [
                    'user_id' => $userId,
                    'preferences_set' => count($preferences)
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to set multiple preferences'
        ], 500);
    }
}
