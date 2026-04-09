<?php

namespace App\Controller;

use App\Service\CloudinaryService;
use App\Repository\PlatRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\Security;

#[Route('/api/plat', name: 'app_api_plat_photo_')]
class PlatPhotoController extends AbstractController
{
    public function __construct(
        private PlatRepository $plats,
        private EntityManagerInterface $em,
        private CloudinaryService $cloudinaryService
    ) {}

    #[Route('/{id}/photo', name: 'upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[Security("is_granted('ROLE_EMPLOYEE') or is_granted('ROLE_ADMIN')")]
    #[OA\Post(
        path: '/api/plat/{id}/photo',
        summary: "Uploader/remplacer la photo d’un plat",
        tags: ['Plat'],
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'photo', type: 'string', format: 'binary')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Photo mise à jour'),
            new OA\Response(response: 400, description: 'Fichier manquant / format invalide'),
            new OA\Response(response: 404, description: 'Plat introuvable'),
        ]
    )]
    public function upload(int $id, Request $request): JsonResponse
    {
        $plat = $this->plats->find($id);
        if (!$plat) {
            return new JsonResponse(['message' => 'Plat introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('photo');
        if (!$file) {
            return new JsonResponse(['message' => 'Champ requis: photo'], Response::HTTP_BAD_REQUEST);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array((string) $file->getMimeType(), $allowed, true)) {
            return new JsonResponse(['message' => 'Format invalide (jpeg/png/webp)'], Response::HTTP_BAD_REQUEST);
        }

        // 1) supprime l'ancienne si existe
        if ($plat->getPhoto()) {
            $this->cloudinaryService->deleteByUrl($plat->getPhoto());
        }

        // 3) move
        $uploaded = $this->cloudinaryService->uploadPlatPhoto($file, $plat->getId());

        if (empty($uploaded['secure_url'])) {
            return new JsonResponse(['message' => 'Échec de l’upload Cloudinary'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        // 4) save bdd
        $plat->setPhoto($uploaded['secure_url']);
        $this->em->flush();

        return new JsonResponse([
            'id' => $plat->getId(),
            'photo' => $plat->getPhoto(),
            'photo_url' => $plat->getPhoto(),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/photo', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Security("is_granted('ROLE_EMPLOYEE') or is_granted('ROLE_ADMIN')")]
    #[OA\Delete(
        path: '/api/plat/{id}/photo',
        summary: "Supprimer la photo d’un plat",
        tags: ['Plat'],
        security: [['X-AUTH-TOKEN' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Photo supprimée'),
            new OA\Response(response: 404, description: 'Plat introuvable'),
        ]
    )]
    public function delete(int $id): JsonResponse
    {
        $plat = $this->plats->find($id);
        if (!$plat) {
            return new JsonResponse(['message' => 'Plat introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($plat->getPhoto()) {
            $path = rtrim($this->platsUploadDir, '/').'/'.$plat->getPhoto();
            if (is_file($path)) {
                @unlink($path);
            }
            $plat->setPhoto(null);
            $this->em->flush();
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
