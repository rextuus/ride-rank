<?php

declare(strict_types=1);

namespace App\Service\Player;

use App\Entity\Player;
use App\Entity\User;
use App\Repository\LocationRepository;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class PlayerContext
{
    private ?Player $currentPlayer = null;

    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly LocationRepository $locationRepository,
    ) {
    }

    public function getCurrentPlayer(): Player
    {
        if ($this->currentPlayer !== null) {
            return $this->currentPlayer;
        }

        $user = $this->security->getUser();
        $request = $this->requestStack->getMainRequest();

        // 1️⃣ Logged-in user → fetch or create Player
        if ($user instanceof User) {
            $player = $this->playerRepository->findOneBy(['user' => $user]);
            if (!$player) {
                $player = new Player();
                $player->attachUser($user);

                $this->em->persist($player);
                $this->em->flush();
            }

            $player->touch();
            $this->em->flush();

            return $this->currentPlayer = $player;
        }

        // 2️⃣ Anonymous user → use device hash or session
        $deviceHash = $request?->cookies->get('player_hash');
        if ($deviceHash) {
            $player = $this->playerRepository->findOneBy(['deviceHash' => $deviceHash]);
            if ($player instanceof Player) {
                $player->touch();
                $this->em->flush();

                return $this->currentPlayer = $player;
            }
        }

        // 3️⃣ No existing player → create new
        $player = new Player();
        $player->setDeviceHash(bin2hex(random_bytes(16)));

        // 🌍 Detect visitor country
        $countryCode = $this->detectCountryFromRequest($request);
        if ($countryCode !== null) {
            $locationIdent = IsoToLocationIdent::from($countryCode);
            $country = $this->locationRepository->findOneBy(['ident' => $locationIdent->name]);

            $player->setHomeCountry($country); // Assuming Player has a `homeCountry` field
        }

        $this->em->persist($player);
        $this->em->flush();

        if ($request) {
            $request->getSession()->set('player_device_hash', $player->getDeviceHash());
        }

        return $this->currentPlayer = $player;
    }

    /**
     * Detect the country of the visitor from the request.
     */
    private function detectCountryFromRequest(?Request $request): ?string
    {
        if (!$request) {
            return null;
        }

        // 1️⃣ CloudFlare header (if behind CloudFlare)
        $country = $request->headers->get('CF-IPCountry');
        if ($country && $country !== 'XX') {
            return $country;
        }

        // 2️⃣ Other CDN headers (Akamai, etc.)
        $country = $request->headers->get('X-Country-Code');
        if ($country) {
            return $country;
        }

        // 3️⃣ Accept-Language fallback (very unreliable)
        $language = $request->headers->get('Accept-Language');
        if ($language) {
            // Extract first language: "de-DE,de;q=0.9,en;q=0.8" → "de"
            $locale = substr($language, 0, 2);
            return strtoupper($locale);
        }

        return null;
    }

    /**
     * Attach a logged-in user to the current anonymous player.
     */
    public function attachUserToCurrentPlayer(User $user): void
    {
        $player = $this->getCurrentPlayer();
        if ($player->isAnonymous()) {
            $player->attachUser($user);
            $this->em->flush();
        }
    }
}
