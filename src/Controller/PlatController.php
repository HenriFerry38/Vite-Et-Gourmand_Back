<?php

namespace App\Controller;
use App\Repository\PlatRepository;
use App\Repository\AllergeneRepository;
use App\Entity\Plat;
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
use Symfony\Component\Security\Http\Attribute\Security;

#[Route('/api/plat', name: 'app_api_plat_')]
class PlatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PlatRepository $repository,
        private AllergeneRepository $allergeneRepo,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator
        )
    {

    }
    #[Route( name: 'new', methods: ['POST'])]
    #[Security("is_granted('ROLE_EMPLOYEE') or is_granted('ROLE_ADMIN')")]
    #[OA\Post(
        path: '/api/plat',
        summary: "Créer un nouveau plat",
        tags: ['Plat'],
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['titre', 'categorie'],
                properties: [
                    new OA\Property(property: 'titre', type: 'string', example: 'Poulet rôti'),
                    new OA\Property(property: 'categorie', type: 'string', example: 'plat'),
                    new OA\Property(property: 'photo', type: 'string', nullable: true, example: null),
                ]
            )
        )
    )]
    public function new(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '[]', true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $titre = trim((string)($data['titre'] ?? ''));
        $categorie = trim((string)($data['categorie'] ?? ''));

        if ($titre === '' || $categorie === '') {
            return new JsonResponse(['error' => 'Champs requis: titre, categorie'], Response::HTTP_BAD_REQUEST);
        }

        $plat = new Plat();
        $plat->setTitre($titre);
        $plat->setCategorie($categorie);

        if (array_key_exists('photo', $data)) {
            $plat->setPhoto($data['photo'] ?: null);
        }

        $plat->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($plat);
        $this->manager->flush();

        $json = $this->serializer->serialize($plat, 'json', [
            'groups' => ['plat:read'],
            'circular_reference_handler' => fn($object) => method_exists($object, 'getId') ? $object->getId() : null,
        ]);

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }
    

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/api/plat/{id}',
        summary: "Afficher un plat par ID",
        description: "Retourne un plat à partir de son identifiant",
        tags: ['Plat'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant du plat",
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Plat trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'titre', type: 'string', example: 'Poulet rôti'),
                        new OA\Property(
                            property: 'photo',
                            type: 'string',
                            nullable: true,
                            example: 'https://exemple.com/images/poulet-roti.jpg'
                        ),
                        new OA\Property(
                            property: 'createdAt',
                            type: 'string',
                            format: 'date-time'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Plat non trouvé"
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $plat = $this->repository->findOneBy(['id' => $id]);
        if ($plat) {
            $responseData = $this->serializer->serialize($plat, 'json',[
                'groups' => ['plat:read'],
                'circular_reference_handler' => function ($object) {
                return method_exists($object, 'getId') ? $object->getId() : null;
            },
        ]);

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    } 

    #[Route('/{id}', name: 'edit', methods: ['PUT'],requirements: ['menuId' => '\d+', 'platId' => '\d+'])]
    #[Security("is_granted('ROLE_EMPLOYEE') or is_granted('ROLE_ADMIN')")]
    #[OA\Put(
        path: '/api/plat/{id}',
        summary: "Modifier un plat par ID",
        description: "Met à jour un plat existant à partir de son identifiant",
        tags: ['Plat'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant du plat",
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                // PUT “souple” : tu peux laisser tout optionnel si ton controller le permet
                properties: [
                    new OA\Property(property: 'titre', type: 'string', example: 'Poulet rôti (version XL)'),
                    new OA\Property(
                        property: 'photo',
                        type: 'string',
                        nullable: true,
                        example: 'https://exemple.com/images/poulet-roti-xl.jpg'
                    ),
                    new OA\Property(property: 'categorie', type: 'string', example: 'plat'),
                    new OA\Property(
                        property: 'allergenesIds',
                        type: 'array',
                        items: new OA\Items(type: 'integer', example: 1),
                        example: [1, 3, 5],
                        description: "Liste d’IDs d’allergènes à associer au plat (remplace la liste existante)."
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Plat modifié (pas de contenu retourné)"
            ),
            new OA\Response(
                response: 400,
                description: "Requête invalide"
            ),
            new OA\Response(
                response: 404,
                description: "Plat non trouvé"
            )
        ]
    )]
    public function edit(int $id, Request $request): JsonResponse
    {
        $plat = $this->repository->findOneBy(['id' => $id]);
        if ($plat) {
            $raw = $request->getContent() ?: '';
            $data = json_decode($raw, true);

            if ($data === null && $raw !== '' && json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['message' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
            }

            $plat = $this->serializer->deserialize(
                $raw,
                Plat::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $plat]
            );

            if (is_array($data) && array_key_exists('allergenesIds', $data)) {
                $ids = $data['allergenesIds'];

                if (!is_array($ids)) {
                    return new JsonResponse(['message' => 'allergenesIds doit être un tableau'], Response::HTTP_BAD_REQUEST);
                }

                // reset
                foreach ($plat->getAllergenes() as $al) {
                    $plat->removeAllergene($al);
                }

                // add new
                foreach ($ids as $alId) {
                    $alId = (int) $alId;
                    if ($alId <= 0) continue;

                    $all = $this->allergeneRepo->find($alId);
                    if ($all) {
                        $plat->addAllergene($all);
                    }
                }
            }

            $plat->setUpdatedAt(new DateTimeImmutable());
            $this->manager->flush();

            return new JsonResponse( null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    }

    
    #[Route('/{id}', name: 'delete', methods: ['DELETE'],requirements: ['menuId' => '\d+', 'platId' => '\d+'])]
    #[Security("is_granted('ROLE_EMPLOYEE') or is_granted('ROLE_ADMIN')")]
    #[OA\Delete(
        path: '/api/plat/{id}',
        summary: "Supprimer un plat par ID",
        description: "Supprime un plat à partir de son identifiant",
        tags: ['Plat'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: "Identifiant du plat",
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "Plat supprimé avec succès"
            ),
            new OA\Response(
                response: 404,
                description: "Plat non trouvé"
            )
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $plat = $this->repository->findOneBy(['id' => $id]);
        if ($plat) {
            foreach ($plat->getMenus() as $menu) {
                $menu->removePlat($plat); // faut que Menu ait removePlat()
            }

            $photo = $plat->getPhoto();
            if ($photo) {
                $path = $this->getParameter('kernel.project_dir') . '/public/uploads/plats/' . $photo;
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $this->manager->remove($plat);
            $this->manager->flush();

            return new JsonResponse( null, Response::HTTP_NO_CONTENT);
        }
        
        return new JsonResponse( null, Response::HTTP_NOT_FOUND);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[Security("is_granted('ROLE_EMPLOYEE') or is_granted('ROLE_ADMIN')")]
    #[OA\Get(
        path: '/api/plat',
        summary: 'Lister les plats',
        tags: ['Plat'],
        security: [['X-AUTH-TOKEN' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des plats',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'object')
                )
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $plats = $this->repository->findBy([], ['id' => 'DESC']);

        $json = $this->serializer->serialize($plats, 'json', ['groups' => ['plat:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

}