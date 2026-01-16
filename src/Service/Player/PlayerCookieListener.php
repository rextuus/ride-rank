<?php

declare(strict_types=1);

namespace App\Service\Player;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onResponse')]
final class PlayerCookieListener
{
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only set cookie if we stored a new device hash in session
        if ($request->getSession()->has('player_device_hash')) {
            $deviceHash = $request->getSession()->get('player_device_hash');

            // Remove from session so we don't keep setting it
            $request->getSession()->remove('player_device_hash');

            // Set the cookie (expires in 1 year, adjust as needed)
            $cookie = Cookie::create('player_hash')
                ->withValue($deviceHash)
                ->withExpires(time() + (365 * 24 * 60 * 60))
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX);

            $response->headers->setCookie($cookie);
        }
    }
}
