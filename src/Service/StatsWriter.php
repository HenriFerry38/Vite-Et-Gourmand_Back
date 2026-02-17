<?php

namespace App\Service;

use App\Entity\Commande;

final class StatsWriter
{
    public function __construct(private Mongo $mongo) {}

    public function addCommandeToDailyMenuStats(Commande $commande): void
    {
        $menu = $commande->getMenu();
        if (!$menu) return;

        $day = $commande->getDateCommande()?->format('Y-m-d') ?? (new \DateTimeImmutable())->format('Y-m-d');

        $menuId = $menu->getId();
        $menuTitre = $menu->getTitre();

        $nb = (int) ($commande->getNbPersonne() ?? 0);
        $prixParPersonne = (float) ($menu->getPrixParPersonne() ?? 0);
        $revenue = $prixParPersonne * $nb;

        $col = $this->mongo->collection('menu_stats_daily');

        $col->updateOne(
            ['day' => $day, 'menuId' => $menuId],
            [
                '$setOnInsert' => ['menuTitre' => $menuTitre],
                '$inc' => ['ordersCount' => 1, 'revenue' => $revenue],
            ],
            ['upsert' => true]
        );
    }
}
