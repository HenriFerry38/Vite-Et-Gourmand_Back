<?php

namespace App\Controller;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Entity\Avis;
use App\Entity\User;
use App\Enum\StatutAvis;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api/avis', name: 'app_api_avis_')]
class AvisController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AvisRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private CommandeRepository $commandeRepository,
         private MailerInterface $mailer
        )
    {

    }
    #[Route( name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/avis',
        summary: "Créer un avis",
        description: "Crée un avis pour une commande terminée. L’avis est créé avec le statut EN_ATTENTE (validation employé requise).",
        tags: ['Avis'],
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données nécessaires à la création de l’avis",
            content: new OA\JsonContent(
                required: ['commande_id', 'note', 'description'],
                properties: [
                    new OA\Property(
                        property: 'commande_id',
                        type: 'integer',
                        example: 24,
                        description: "ID de la commande terminée sur laquelle l’avis porte"
                    ),
                    new OA\Property(
                        property: 'note',
                        type: 'integer',
                        minimum: 1,
                        maximum: 5,
                        example: 5,
                        description: "Note attribuée (1 à 5)"
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        maxLength: 255,
                        example: "Excellent service et plats délicieux",
                        description: "Commentaire de l’avis"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Avis créé (EN_ATTENTE)",
                headers: [
                    new OA\Header(
                        header: 'Location',
                        description: "URL de l’avis créé",
                        schema: new OA\Schema(type: 'string', example: 'http://localhost/api/avis/1')
                    )
                ],
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'commande', type: 'integer', example: 24, description: "ID commande (référence légère)"),
                        new OA\Property(property: 'note', type: 'integer', example: 5),
                        new OA\Property(property: 'description', type: 'string', example: "Excellent service et plats délicieux"),
                        new OA\Property(property: 'statut', type: 'string', enum: ['en_attente','accepte','refuse'], example: 'en_attente'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-15T14:20:00+01:00'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Requête invalide (JSON incorrect, champs manquants, note hors limite, statut invalide)"
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié (token manquant ou invalide)"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (la commande n’appartient pas à l’utilisateur)"
            ),
            new OA\Response(
                response: 404,
                description: "Commande introuvable"
            ),
            new OA\Response(
                response: 409,
                description: "Conflit (commande non terminée ou avis déjà existant pour cette commande)"
            ),
        ]
    )]
    public function new(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['message' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $note = $data['note'] ?? null;
        $description = $data['description'] ?? null;
        $commandeId = $data['commande_id'] ?? null;

        if (!$note || !$description || !$commandeId) {
            return new JsonResponse(['message' => 'Champs requis: note, description, commande_id'], Response::HTTP_BAD_REQUEST);
        }

        $note = (int) $note;
        if ($note < 1 || $note > 5) {
            return new JsonResponse(['message' => 'note doit être entre 1 et 5'], Response::HTTP_BAD_REQUEST);
        }

        $commande = $this->commandeRepository->find((int) $commandeId);
        if (!$commande) {
            return new JsonResponse(['message' => 'Commande introuvable'], Response::HTTP_NOT_FOUND);
        }

        // ✅ vérif: la commande appartient au user
        if ($commande->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // ✅ vérif: commande terminée (adapte selon ton enum StatutCommande)
        if ($commande->getStatut()->value !== 'terminee') {
            return new JsonResponse(['message' => 'Avis possible uniquement sur une commande terminée'], Response::HTTP_CONFLICT);
        }

        // ✅ anti doublon: avis déjà existant sur cette commande
        $existing = $this->repository->findOneBy(['commande' => $commande]);
        if ($existing) {
            return new JsonResponse(['message' => 'Un avis existe déjà pour cette commande'], Response::HTTP_CONFLICT);
        }

        $avis = new Avis();
        $avis->setNote($note);
        $avis->setDescription((string) $description);
        $avis->setUser($user);
        $avis->setCommande($commande);
        $avis->setStatut(StatutAvis::EN_ATTENTE);
        $avis->setCreatedAt(new \DateTimeImmutable());

        $this->manager->persist($avis);
        $this->manager->flush();

        $json = $this->serializer->serialize($avis, 'json', [
            'circular_reference_handler' => fn ($object) => method_exists($object, 'getId') ? $object->getId() : null,
        ]);

        $location = $this->urlGenerator->generate(
            'app_api_avis_show',
            ['id' => $avis->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($json, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/avis/{id}',
        summary: "Consulter un avis par ID",
        description: "Retourne le détail d’un avis à partir de son identifiant.",
        tags: ['Avis'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant de l’avis",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Avis trouvé",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'note', type: 'integer', example: 5),
                        new OA\Property(property: 'commentaire', type: 'string', example: 'Excellent service, je recommande !'),
                        new OA\Property(
                            property: 'createdAt',
                            type: 'string',
                            format: 'date-time',
                            example: '2026-01-12T14:30:00+01:00'
                        ),
                        new OA\Property(
                            property: 'updatedAt',
                            type: 'string',
                            format: 'date-time',
                            nullable: true,
                            example: '2026-01-13T09:00:00+01:00'
                        ),
                        new OA\Property(
                            property: 'statut',
                            type: 'string',
                            description: "Statut de validation de l’avis",
                            enum: ['en_attente', 'accepte', 'refuse'],
                            example: 'accepte'
                        ),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            description: "Auteur de l’avis (format léger)",
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 12),
                                new OA\Property(property: 'email', type: 'string', example: 'client@email.com'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Avis introuvable"
            ),
        ]
    )]

    public function show(int $id): JsonResponse
    {
        $avis = $this->repository->findOneBy(['id' => $id]);
        if ($avis) {
            $responseData = $this->serializer->serialize($avis, 'json', [
            'circular_reference_handler' => fn ($object) =>
            method_exists($object, 'getId') ? $object->getId() : null,
        ]);

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    } 
 

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/avis/{id}',
        summary: "Modifier un avis par ID",
        description: "Met à jour un avis existant. Selon les règles métier, un avis est généralement modifiable par son auteur tant qu’il est en attente, ou par un employé/admin.",
        tags: ['Avis'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant de l’avis",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Champs modifiables d’un avis",
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'note',
                        type: 'integer',
                        minimum: 1,
                        maximum: 5,
                        example: 4
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        example: 'Service très agréable, livraison ponctuelle.'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Avis mis à jour (aucun contenu retourné)"
            ),
            new OA\Response(
                response: 400,
                description: "Requête invalide (JSON invalide ou données incorrectes)"
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (droits insuffisants ou avis non modifiable)"
            ),
            new OA\Response(
                response: 404,
                description: "Avis introuvable"
            ),
        ]
    )]

    public function edit(int $id, Request $request): JsonResponse
    {
        $avis = $this->repository->findOneBy(['id' => $id]);
        if ($avis) {
            $avis = $this->serializer->deserialize(
                $request->getContent(),
                Avis::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $avis]
            );
            $avis->setUpdatedAt(new DateTimeImmutable());
            $this->manager->flush();

            return new JsonResponse( null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    }
    
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/avis/{id}',
        summary: "Supprimer un avis par ID",
        description: "Supprime définitivement un avis.\n\nSelon les règles métier, la suppression peut être réservée à un employé ou un administrateur, ou à l’auteur tant que l’avis est en attente.",
        tags: ['Avis'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant de l’avis à supprimer",
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Avis supprimé avec succès (aucun contenu retourné)"
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (droits insuffisants ou avis non supprimable)"
            ),
            new OA\Response(
                response: 404,
                description: "Avis introuvable"
            ),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $avis = $this->repository->findOneBy(['id' => $id]);
        if ($avis) {
            $this->manager->remove($avis);
            $this->manager->flush();

            return new JsonResponse( null, Response::HTTP_NO_CONTENT);
        }
        
        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}/accepter', name: 'accepter', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    #[OA\Post(
        path: '/api/avis/{id}/accepter',
        summary: "Accepter un avis",
        description: "Passe le statut de l'avis à 'accepte' (uniquement si l'avis est en_attente).",
        tags: ['Employé'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant de l'avis",
                schema: new OA\Schema(type: 'integer', example: 12)
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Avis accepté (aucun contenu retourné)"),
            new OA\Response(
                response: 404,
                description: "Avis introuvable",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [new OA\Property(property: 'message', type: 'string', example: 'Avis introuvable')]
                )
            ),
            new OA\Response(
                response: 409,
                description: "Transition de statut impossible",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: "Impossible d'accepter un avis qui n'est pas en attente"),
                        new OA\Property(property: 'statutActuel', type: 'string', example: 'refuse'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (rôle insuffisant)"
            ),
        ]
    )]
    public function accepter(int $id): JsonResponse
    {
        /** @var Avis|null $avis */
        $avis = $this->repository->find($id);

        if (!$avis) {
            return new JsonResponse(['message' => 'Avis introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($avis->getStatut() !== StatutAvis::EN_ATTENTE) {
            return new JsonResponse([
                'message' => "Impossible d'accepter un avis qui n'est pas en attente",
                'statutActuel' => $avis->getStatut()->value,
            ], Response::HTTP_CONFLICT);
        }

        $avis->setStatut(StatutAvis::ACCEPTE);
        $avis->setUpdatedAt(new DateTimeImmutable());

        $this->manager->flush();

        $to = $avis->getUser()?->getEmail();
        if ($to) {
            $email = (new Email())
                ->from('no-reply@viteetgourmand.fr')
                ->to($to)
                ->subject('Votre avis a été publié ✅')
                ->text(
                    "Bonjour,\n\nVotre avis a été accepté et publié. Merci pour votre retour !\n\n— Vite & Gourmand"
                );

            $this->mailer->send($email);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/refuser', name: 'refuser', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    #[OA\Post(
        path: '/api/avis/{id}/refuser',
        summary: "Refuser un avis",
        description: "Passe le statut de l'avis à 'refuse' (uniquement si l'avis est en_attente).",
        tags: ['Employé'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant de l'avis",
                schema: new OA\Schema(type: 'integer', example: 12)
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "Avis refusé (aucun contenu retourné)"),
            new OA\Response(
                response: 404,
                description: "Avis introuvable",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [new OA\Property(property: 'message', type: 'string', example: 'Avis introuvable')]
                )
            ),
            new OA\Response(
                response: 409,
                description: "Transition de statut impossible",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: "Impossible de refuser un avis qui n'est pas en attente"),
                        new OA\Property(property: 'statutActuel', type: 'string', example: 'accepte'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié"
            ),
            new OA\Response(
                response: 403,
                description: "Accès refusé (rôle insuffisant)"
            ),
        ]
    )]

    public function refuser(int $id): JsonResponse
    {
        /** @var Avis|null $avis */
        $avis = $this->repository->find($id);

        if (!$avis) {
            return new JsonResponse(['message' => 'Avis introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($avis->getStatut() !== StatutAvis::EN_ATTENTE) {
            return new JsonResponse([
                'message' => "Impossible de refuser un avis qui n'est pas en attente",
                'statutActuel' => $avis->getStatut()->value,
            ], Response::HTTP_CONFLICT);
        }

        $avis->setStatut(StatutAvis::REFUSE);
        $avis->setUpdatedAt(new DateTimeImmutable());

        $this->manager->flush();

        $to = $avis->getUser()?->getEmail();
        if ($to) {
            $email = (new Email())
                ->from('no-reply@viteetgourmand.fr')
                ->to($to)
                ->subject('Votre avis a été refusé ❌')
                ->text(
                    "Bonjour,\n\nVotre avis n’a pas pu être publié car il ne respecte pas les conditions de dépôt d’avis.\n" .
                    "Si vous pensez qu’il s’agit d’une erreur, vous pouvez nous contacter.\n\n— Vite & Gourmand"
                );

            $this->mailer->send($email);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/avis',
        summary: "Lister les avis",
        description: "Retourne la liste des avis. Par défaut, retourne les avis acceptés. Un employé/admin peut filtrer par statut.",
        tags: ['Employé'],
        parameters: [
            new OA\Parameter(
                name: 'statut',
                in: 'query',
                required: false,
                description: "Filtrer par statut (en_attente, accepte, refuse). Par défaut: accepte.",
                schema: new OA\Schema(type: 'string', enum: ['en_attente','accepte','refuse'], example: 'accepte')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des avis",
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            )
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $statutParam = $request->query->get('statut');

        // Public: par défaut accepte
        if (!$statutParam) {
            $statutParam = StatutAvis::ACCEPTE->value;
        }

        // Si on demande autre chose que "accepte", il faut être employé/admin
        if ($statutParam !== StatutAvis::ACCEPTE->value && !$this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Convert string -> Enum
        $statut = StatutAvis::tryFrom($statutParam);
        if (!$statut) {
            return new JsonResponse(['message' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
        }

        $avis = $this->repository->findBy(['statut' => $statut], ['createdAt' => 'DESC']);

        $json = $this->serializer->serialize($avis, 'json', [
            'groups' => ['avis:employee'],
            'circular_reference_handler' => fn ($object) => method_exists($object, 'getId') ? $object->getId() : null,
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/by-commande/{commandeId}', name: 'by_commande', methods: ['GET'], requirements: ['commandeId' => '\d+'])]
    #[OA\Get(
        path: '/api/avis/by-commande/{commandeId}',
        summary: "Récupérer l’avis d’une commande",
        description: "Retourne l’avis lié à une commande (si existe).",
        tags: ['Avis'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(
                name: 'commandeId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 24)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Avis trouvé"),
            new OA\Response(response: 404, description: "Aucun avis pour cette commande"),
        ]
    )]
    public function byCommande(int $commandeId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $commande = $this->commandeRepository->find($commandeId);
        if (!$commande) {
            return new JsonResponse(['hasAvis' => false], Response::HTTP_OK);
        }

        // Sécurité
        if (!$this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
            if ($commande->getUser()?->getId() !== $user->getId()) {
                return new JsonResponse(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
            }
        }

        $avis = $this->repository->findOneBy(['commande' => $commande]);

        if (!$avis) {
            return new JsonResponse(['hasAvis' => false], Response::HTTP_OK);
        }

        return new JsonResponse([
            'hasAvis' => true,
            'statut' => $avis->getStatut()->value,
            'id' => $avis->getId()
        ], Response::HTTP_OK);
    }
}