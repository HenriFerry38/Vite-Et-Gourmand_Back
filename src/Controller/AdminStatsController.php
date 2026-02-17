<?php

namespace App\Controller;

use App\Service\Mongo;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/stats', name: 'app_api_admin_stats_')]
final class AdminStatsController extends AbstractController
{
    public function __construct(private Mongo $mongo) {}

    #[Route('/orders-per-menu', name: 'orders_per_menu', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/admin/stats/orders-per-menu',
        summary: 'Nombre de commandes par menu (MongoDB)',
        tags: ['Admin'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(
                name: 'from',
                in: 'query',
                required: false,
                description: "Date de début incluse (YYYY-MM-DD).",
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-02-01')
            ),
            new OA\Parameter(
                name: 'to',
                in: 'query',
                required: false,
                description: "Date de fin incluse (YYYY-MM-DD).",
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-02-19')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Agrégats commandes par menu",
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'menuId', type: 'integer', example: 12),
                            new OA\Property(property: 'menuTitre', type: 'string', example: 'Menu Viandard'),
                            new OA\Property(property: 'ordersCount', type: 'integer', example: 10),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Paramètres invalides (dates invalides)"
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (ROLE_ADMIN requis)"
            ),
        ]
    )]
    public function ordersPerMenu(Request $request): JsonResponse
    {
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $match = [];
        if ($from || $to) {
            $match['day'] = [];
            if ($from) $match['day']['$gte'] = $from;
            if ($to)   $match['day']['$lte'] = $to;
        }

        $col = $this->mongo->collection('menu_stats_daily');

        $pipeline = [
            ['$match' => (object)$match],
            ['$group' => [
                '_id' => ['menuId' => '$menuId', 'menuTitre' => '$menuTitre'],
                'ordersCount' => ['$sum' => '$ordersCount'],
            ]],
            ['$project' => [
                '_id' => 0,
                'menuId' => '$_id.menuId',
                'menuTitre' => '$_id.menuTitre',
                'ordersCount' => 1,
            ]],
            ['$sort' => ['ordersCount' => -1]],
        ];

        return new JsonResponse(iterator_to_array($col->aggregate($pipeline)), 200);
    }

    #[Route('/revenue', name: 'revenue', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/admin/stats/revenue',
        summary: 'Chiffre d’affaires par menu (MongoDB)',
        tags: ['Admin'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(
                name: 'menuId',
                in: 'query',
                required: false,
                description: "Filtrer sur un menu (ID). Si absent, agrège tous les menus.",
                schema: new OA\Schema(type: 'integer', example: 12)
            ),
            new OA\Parameter(
                name: 'from',
                in: 'query',
                required: false,
                description: "Date de début incluse (YYYY-MM-DD).",
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-02-01')
            ),
            new OA\Parameter(
                name: 'to',
                in: 'query',
                required: false,
                description: "Date de fin incluse (YYYY-MM-DD).",
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-02-19')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Agrégats CA par menu",
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'menuId', type: 'integer', example: 12),
                            new OA\Property(property: 'menuTitre', type: 'string', example: 'Menu Viandard'),
                            new OA\Property(property: 'revenue', type: 'number', format: 'float', example: 520.50),
                            new OA\Property(property: 'ordersCount', type: 'integer', example: 10),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: "Paramètres invalides (ex: menuId non numérique, dates invalides)"
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (ROLE_ADMIN requis)"
            ),
        ]
    )]
    public function revenue(Request $request): JsonResponse
    {
        $menuId = $request->query->get('menuId');
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $match = [];
        if ($menuId !== null && $menuId !== '') {
            $match['menuId'] = (int)$menuId;
        }
        if ($from || $to) {
            $match['day'] = [];
            if ($from) $match['day']['$gte'] = $from;
            if ($to)   $match['day']['$lte'] = $to;
        }

        $col = $this->mongo->collection('menu_stats_daily');

        $pipeline = [
            ['$match' => (object)$match],
            ['$group' => [
                '_id' => ['menuId' => '$menuId', 'menuTitre' => '$menuTitre'],
                'revenue' => ['$sum' => '$revenue'],
                'ordersCount' => ['$sum' => '$ordersCount'],
            ]],
            ['$project' => [
                '_id' => 0,
                'menuId' => '$_id.menuId',
                'menuTitre' => '$_id.menuTitre',
                'revenue' => 1,
                'ordersCount' => 1,
            ]],
            ['$sort' => ['revenue' => -1]],
        ];

        return new JsonResponse(iterator_to_array($col->aggregate($pipeline)), 200);
    }
}
