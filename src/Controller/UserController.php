<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Solde;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;



class UserController extends AbstractController
{
    #[Route('/api/login', name: 'api_users_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $repository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['mot_de_passe'])) {
            return $this->json(['message' => 'Invalid credentials.'], 400);
        }

        $user = $repository->findOneBy(['email' => $data['email']]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $data['mot_de_passe'])) {
            return $this->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => $this->serializeUser($user)
        ]);
    }

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    public function listeUsers(UserRepository $repository): JsonResponse
    {
        $users = array_map(
            fn(User $user) => $this->serializeUser($user),
            $repository->findAll()
        );

        return $this->json($users);
    }

    #[Route('/api/users/{id}', name: 'api_users_show', methods: ['GET'])]
    public function findUserById(int $id, UserRepository $repository): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeUser($user));
    }

    #[Route('/api/users/register', name: 'api_users_create', methods: ['POST'])]
    public function inscription(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        foreach ($data as $key => $value) {
            if ($value === '') {
                return $this->errorResponse("{$key} is required", Response::HTTP_BAD_REQUEST);
            }
        }

        $nom = trim((string) ($data['nom'] ?? ''));

        $prenom = trim((string) ($data['prenom'] ?? ''));

        $date_naissance = trim((string) ($data['date_naissance']));
        $date = \DateTime::createFromFormat('d/m/Y', $date_naissance);

        $telephone = trim((string) ($data['telephone'] ?? ''));

        $email = trim((string) ($data['email'] ?? ''));

        $mot_de_passe = trim((string) ($data['mot_de_passe'] ?? ''));
        if ($mot_de_passe === '') {
            return $this->errorResponse('mot de passe is required.', Response::HTTP_BAD_REQUEST);
        }


        $user = (new User())
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setDateNaissance($date)
            ->setTelephone($telephone)
            ->setEmail($email);

        $hashedPassword = $passwordHasher->hashPassword($user, $mot_de_passe);
        $user->setMotDePasse($hashedPassword);


        $entityManager->persist($user);
        $entityManager->flush();

        $solde = new Solde();
        $solde->setUser($user);
        $solde->setMontantSolde(0.0);

        $entityManager->persist($solde);
        $entityManager->flush();


        return $this->json($this->serializeUser($user), Response::HTTP_CREATED);
    }

    #[Route('/api/users/{id}', name: 'api_users_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        UserRepository $repository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $user = $repository->find($id);

        if (!$user) {
            return $this->errorResponse('user not found.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $oldEmail = $user->getUserIdentifier();
        $emailChanged = false;

        foreach ($data as $key => $value) {

            // Si la valeur est null, on ne touche pas au trajet
            if ($value === null) {
                continue;
            }

            switch ($key) {

                case 'nom':
                    $user->setNom($value);
                    break;

                case 'prenom':
                    $user->setPrenom($value);
                    break;


                case 'date_naissance':
                    $date = \DateTime::createFromFormat('d/m/Y', $value);
                    $user->setDateNaissance($date);
                    break;

                case 'telephone':
                    $user->setTelephone($value);
                    break;

                case 'email':
                    if ($value !== $oldEmail) {
                        $emailChanged = true;
                    }
                    $user->setEmail($value);
                    break;


                case 'mot_de_passe':
                    $hashedPassword = $passwordHasher->hashPassword($user, $value);
                    $user->setMotDePasse($hashedPassword);
            }
        }

        $entityManager->flush();

        $response = [
            'user' => $this->serializeUser($user),
        ];

        if ($emailChanged) {
            $token = $jwtManager->create($user);
            $response['token'] = $token;
        }

        // Sinon retouner le détails du produit avec le status HTTP 302
        return $this->json($response, Response::HTTP_OK);
    }

    #[Route('/api/users/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function deleteUserById(int $id, UserRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->errorResponse('user not found.', Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function decodeJson(Request $request): ?array
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $payload;
    }


    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'date_naissance' => $user->getDateNaissance()->format('d/m/Y'),
            'telephone' => $user->getTelephone(),
            'email' => $user->getUserIdentifier(),
            'solde' => $user->getSolde(),

        ];
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}
