<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Solde;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserController extends AbstractController
{
    #[Route('/api/login', name: 'api_users_login', methods: ['POST'])]
    public function login(): void
    {
    }

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    public function list(UserRepository $repository): JsonResponse
    {
        $users = array_map(
            fn(User $user) => $this->serializeUser($user),
            $repository->findAll()
        );

        return $this->json($users);
    }

    #[Route('/api/users/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id, UserRepository $repository): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeUser($user));
    }

    #[Route('/api/users/register', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $nom = trim((string) ($data['nom'] ?? ''));
        if ($nom === '') {
            return $this->errorResponse('Nom is required.', Response::HTTP_BAD_REQUEST);
        }

        $prenom = trim((string) ($data['prenom'] ?? ''));
        if ($prenom === '') {
            return $this->errorResponse('Prenom is required.', Response::HTTP_BAD_REQUEST);
        }

        $age = trim((int) ($data['age'] ?? ''));
        if ($age === '') {
            return $this->errorResponse('age is required.', Response::HTTP_BAD_REQUEST);
        }

        $telephone = trim((string) ($data['telephone'] ?? ''));
        if ($telephone === '') {
            return $this->errorResponse('telephone is required.', Response::HTTP_BAD_REQUEST);
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            return $this->errorResponse('Email is required.', Response::HTTP_BAD_REQUEST);
        }


        $permis_de_conduire = trim((bool) ($data['permis_de_conduire'] ?? ''));


        $mot_de_passe = trim((string) ($data['mot_de_passe'] ?? ''));
        if ($mot_de_passe === '') {
            return $this->errorResponse('mot de passe is required.', Response::HTTP_BAD_REQUEST);
        }


        $user = (new User())
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setAge($age)
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
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $repository->find($id);

        if (!$user) {
            return $this->errorResponse('user not found.', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->errorResponse('Invalid JSON body.', Response::HTTP_BAD_REQUEST);
        }

        $isPut = $request->getMethod() === 'PUT';

        if (array_key_exists('nom', $data) || $isPut) {
            $nom = trim((string) ($data['nom'] ?? ''));
            if ($nom === '') {
                return $this->errorResponse('Nom is required.', Response::HTTP_BAD_REQUEST);
            }
            $user->setNom($nom);
        }

        if (array_key_exists('prenom', $data) || $isPut) {
            $prenom = trim((string) ($data['prenom'] ?? ''));
            if ($prenom === '') {
                return $this->errorResponse('prenom is required.', Response::HTTP_BAD_REQUEST);
            }
            $user->setPrenom($prenom);
        }

        if (array_key_exists('age', $data) || $isPut) {
            $age = trim((int) ($data['age'] ?? ''));
            if ($age === '') {
                return $this->errorResponse('age is required.', Response::HTTP_BAD_REQUEST);
            }
            $user->setAge($age);
        }

        if (array_key_exists('telephone', $data) || $isPut) {
            $telephone = trim((string) ($data['telephone'] ?? ''));
            if ($telephone === '') {
                return $this->errorResponse('telephone is required.', Response::HTTP_BAD_REQUEST);
            }
            $user->setTelephone($telephone);
        }

        if (array_key_exists('email', $data) || $isPut) {
            $email = trim((string) ($data['email'] ?? ''));
            if ($email === '') {
                return $this->errorResponse('email is required.', Response::HTTP_BAD_REQUEST);
            }
            $user->setEmail($email);
        }


        if (array_key_exists('mot de passe', $data) || $isPut) {
            $mot_de_passe = trim((string) ($data['mot de passe'] ?? ''));
            if ($mot_de_passe === '') {
                return $this->errorResponse('mot de passe is required.', Response::HTTP_BAD_REQUEST);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $mot_de_passe);
            $user->setMotdepasse($hashedPassword);
        }


        if (array_key_exists('compte valide', $data) || $isPut) {
            $compte_valide = trim((bool) ($data['compte valide'] ?? ''));
            if ($compte_valide === '') {
                return $this->errorResponse('compte valide is required.', Response::HTTP_BAD_REQUEST);
            }
            $user->setCompteValide($compte_valide);
        }




        $entityManager->flush();

        return $this->json($this->serializeUser($user));
    }

    #[Route('/api/users/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(int $id, UserRepository $repository, EntityManagerInterface $entityManager): JsonResponse
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
            'age' => $user->getAge(),
            'telephone' => $user->getTelephone(),
            'email' => $user->getUserIdentifier(),
            'permis_de_conduire' => $user->getPermisDeConduire(),
            'compte_valide' => $user->getCompteValide(),
            'solde' => $user->getSolde(),

        ];
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return $this->json(['error' => $message], $status);
    }
}
