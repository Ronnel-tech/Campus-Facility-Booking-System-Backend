<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\NotificationPreference;

class UsersController extends \Controller
{
    public function getProfile()
    {
        try {
            $user = auth()->user();
            $profile = UserProfile::where('user_id', $user->id)->first();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                    'profile' => $profile,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch profile',
            ], 400);
        }
    }

    public function updateProfile()
    {
        try {
            $user = auth()->user();
            $profile = UserProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                $profile = UserProfile::create(['user_id' => $user->id]);
            }

            $profile->update([
                'first_name' => request()->get('first_name', $profile->first_name),
                'last_name' => request()->get('last_name', $profile->last_name),
                'phone' => request()->get('phone', $profile->phone),
                'department' => request()->get('department', $profile->department),
                'student_id' => request()->get('student_id', $profile->student_id),
                'faculty_id' => request()->get('faculty_id', $profile->faculty_id),
                'staff_id' => request()->get('staff_id', $profile->staff_id),
                'bio' => request()->get('bio', $profile->bio),
            ]);

            if (request()->has('email') && request()->get('email') !== $user->email) {
                $user->update(['email' => request()->get('email')]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user,
                    'profile' => $profile,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function changePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        try {
            $user = auth()->user();

            if (!verify_password(request()->get('current_password'), $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password is incorrect',
                ], 400);
            }

            $user->update(['password' => bcrypt(request()->get('new_password'))]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to change password',
            ], 400);
        }
    }

    public function getNotificationPreferences()
    {
        try {
            $user = auth()->user();
            $preferences = NotificationPreference::where('user_id', $user->id)->first();

            if (!$preferences) {
                $preferences = NotificationPreference::create(['user_id' => $user->id]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $preferences,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch preferences',
            ], 400);
        }
    }

    public function updateNotificationPreferences()
    {
        try {
            $user = auth()->user();
            $preferences = NotificationPreference::where('user_id', $user->id)->first();

            if (!$preferences) {
                $preferences = NotificationPreference::create(['user_id' => $user->id]);
            }

            $preferences->update([
                'email_reservation_approved' => request()->get('email_reservation_approved', $preferences->email_reservation_approved),
                'email_reservation_rejected' => request()->get('email_reservation_rejected', $preferences->email_reservation_rejected),
                'email_asset_checkout' => request()->get('email_asset_checkout', $preferences->email_asset_checkout),
                'email_asset_return_reminder' => request()->get('email_asset_return_reminder', $preferences->email_asset_return_reminder),
                'email_booking_reminder' => request()->get('email_booking_reminder', $preferences->email_booking_reminder),
                'push_notification_enabled' => request()->get('push_notification_enabled', $preferences->push_notification_enabled),
                'sms_reminder_enabled' => request()->get('sms_reminder_enabled', $preferences->sms_reminder_enabled),
                'reminder_before_hours' => request()->get('reminder_before_hours', $preferences->reminder_before_hours),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Preferences updated successfully',
                'data' => $preferences,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update preferences',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
