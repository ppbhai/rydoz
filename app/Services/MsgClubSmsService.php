<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MsgClubSmsService
{
    public function sendOtp(string $mobile, string $otp, string $name): bool
    {
        $authKey = config('services.msgclub.auth_key');

        if (!$authKey) {
            throw new RuntimeException('MSGClub auth key is not configured.');
        }

        $mobile = preg_replace('/\D/', '', $mobile);

        $message = "Hi {$name}, Your OTP for Rydoz Ride is {$otp}.Ride Your Zone. Thanks Rydoz";
        
        $response = Http::timeout(15)->get(config('services.msgclub.base_url'), [
            'AUTH_KEY' => $authKey,
            'message' => $message,
            'senderId' => config('services.msgclub.sender_id'),
            'routeId' => config('services.msgclub.route_id'),
            'mobileNos' => $mobile,
            'smsContentType' => config('services.msgclub.sms_content_type', 'english'),
            'entityid' => config('services.msgclub.entity_id'),
            'tmid' => config('services.msgclub.tmid'),
            'templateid' => config('services.msgclub.template_id'),
            'concentFailoverId' => config('services.msgclub.concent_failover_id'),
        ]);

        Log::info('MSGClub OTP response.', [
            'mobile' => $mobile,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            Log::warning('MSGClub OTP request failed.', [
                'mobile' => $mobile,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        $body = strtolower($response->body());

        if (
            str_contains($body, 'error') ||
            str_contains($body, 'fail') ||
            str_contains($body, 'invalid')
        ) {
            Log::warning('MSGClub OTP response reported failure.', [
                'mobile' => $mobile,
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
