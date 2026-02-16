<?php

namespace App\Controller;
use App\Repository\AllergeneRepository;
use App\Entity\Allergene;
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

#[Route('/api/allergene', name: 'app_api_allergene_')]
class AllergeneController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AllergeneRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
        )
    {

    }
    #[Route(name: 'new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/allergene',
        summary: "Créer un nouvel allergène",
        description: "Crée un allergène et retourne la ressource créée",
        tags: ['Allergenes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['libelle'],
                properties: [
                    new OA\Property(
                        property: 'libelle',
                        type: 'string',
                        example: 'Gluten'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Allergène créé avec succès',
                headers: [
                    new OA\Header(
                        header: 'Location',
                        description: 'URL de la ressource créée',
                        schema: new OA\Schema(type: 'string', example: 'http://localhost/api/allergene/1')
                    )
                ],
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'libelle', type: 'string', example: 'Gluten'),
                        new OA\Property(
                            property: 'createdAt',
                            type: 'string',
                            format: 'date-time',
                            example: '2026-01-12T10:30:00+01:00'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide (JSON incorrect ou champ manquant)'
            )
        ]
    )]

    public function new(Request $request): JsonResponse
    {   
        $allergene = $this->serializer->deserialize($request->getContent(), Allergene::class, 'json');
        $allergene->setCreatedAt(new DateTimeImmutable());
       
        $this->manager->persist($allergene);
        $this->manager->flush();

        $responseData = $this->serializer->serialize($allergene, 'json');
        $location = $this->urlGenerator->generate(
            'app_api_allergene_show',
            ['id' => $allergene->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return new JsonResponse( $responseData, Response::HTTP_CREATED, ["Location" => $location], true);
    } 
    

    #[Route('/{id}', name: 'show', methods: ['GET'],requirements: ['menuId' => '\d+', 'platId' => '\d+'])]
    #[OA\Get(
        path: '/api/allergene/{id}',
        summary: "Récupérer un allergène par ID",
        description: "Retourne un allergène si l'ID existe",
        tags: ['Allergenes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant de l'allergène",
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Allergène trouvé",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'libelle', type: 'string', example: 'Gluten'),
                        new OA\Property(
                            property: 'createdAt',
                            type: 'string',
                            format: 'date-time',
                            example: '2026-01-12T10:30:00+01:00'
                        ),
                        new OA\Property(
                            property: 'updatedAt',
                            type: 'string',
                            format: 'date-time',
                            nullable: true,
                            example: '2026-01-13T14:10:00+01:00'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Allergène introuvable"
            )
        ]
    )]

    public function show(int $id): JsonResponse
    {
        $allergene = $this->repository->findOneBy(['id' => $id]);
        if ($allergene) {
            $responseData = $this->serializer->serialize($allergene, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    } 

    #[Route('/{id}', name: 'edit', methods: ['PUT'],requirements: ['menuId' => '\d+', 'platId' => '\d+'])]
    #[OA\Put(
        path: '/api/allergene/{id}',
        summary: "Mettre à jour un allergène par ID",
        description: "Met à jour les informations d’un allergène existant",
        tags: ['Allergenes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant de l'allergène",
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données à mettre à jour",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'libelle',
                        type: 'string',
                        example: 'Arachides'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Allergène mis à jour avec succès (aucun contenu retourné)"
            ),
            new OA\Response(
                response: 404,
                description: "Allergène introuvable"
            ),
            new OA\Response(
                response: 400,
                description: "Requête invalide (JSON incorrect)"
            )
        ]
    )]

    public function edit(int $id, Request $request): JsonResponse
    {
        $allergene = $this->repository->findOneBy(['id' => $id]);
        if ($allergene) {
            $allergene = $this->serializer->deserialize(
                $request->getContent(),
                Allergene::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $allergene]
            );
            $allergene->setUpdatedAt(new DateTimeImmutable());
            $this->manager->flush();

            return new JsonResponse( null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    }

    
    #[Route('/{id}', name: 'delete', methods: ['DELETE'],requirements: ['menuId' => '\d+', 'platId' => '\d+'])]
    #[OA\Delete(
        path: '/api/allergene/{id}',
        summary: "Supprimer un allergène par ID",
        description: "Supprime définitivement un allergène existant",
        tags: ['Allergenes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant de l'allergène",
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Allergène supprimé avec succès (aucun contenu retourné)"
            ),
            new OA\Response(
                response: 404,
                description: "Allergène introuvable"
            )
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $allergene = $this->repository->findOneBy(['id' => $id]);
        if ($allergene) {
            $this->manager->remove($allergene);
            $this->manager->flush();

            return new JsonResponse( null, Response::HTTP_NO_CONTENT);
        }
        
        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[Security("is_granted('ROLE_EMPLOYEE') or is_granted('ROLE_ADMIN')")]
    #[OA\Get(
        path: '/api/allergene',
        summary: "Lister les allergènes",
        description: "Retourne la liste des allergènes (réservé employé/admin).",
        tags: ['Allergenes'],
        security: [['X-AUTH-TOKEN' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des allergènes",
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'libelle', type: 'string', example: 'Gluten'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé"),
        ]
    )]
    public function index(): JsonResponse
    {
        $allergenes = $this->repository->findBy([],['id' => 'DESC']);

        $json = $this->serializer->serialize($allergenes,'json', ['groups'=> ['allergene:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}

