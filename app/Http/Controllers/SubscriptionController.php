<?php

namespace App\Http\Controllers;

use App\Managers\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionManager $subscriptionManager
    ) {}

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'subscription' => $this->subscriptionManager->getSubscriptionStatus($request->user()),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json([
            'history' => $this->subscriptionManager->getSubscriptionHistory($request->user()),
        ]);
    }

    public function createCheckout(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'sometimes|in:monthly,yearly',
        ]);

        try {
            $result = $this->subscriptionManager->createCheckout(
                $request->user(),
                $request->input('plan', 'monthly')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCheckoutUrl(Request $request): JsonResponse
    {
        return $this->createCheckout($request);
    }

    public function handleCheckoutSuccess(Request $request): JsonResponse
    {
        $checkoutId = $request->input('checkout_id');

        if (!$checkoutId) {
            return response()->json(['error' => 'Missing checkout_id parameter'], 400);
        }

        try {
            $subscription = $this->subscriptionManager->handleCheckoutSuccess(
                $request->user(),
                $checkoutId
            );

            return response()->json([
                'success' => true,
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        try {
            $result = $this->subscriptionManager->cancelSubscription($request->user());
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPortalUrl(Request $request): JsonResponse
    {
        try {
            $portalUrl = $this->subscriptionManager->getPortalUrl($request->user());
            return response()->json(['portal_url' => $portalUrl]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
