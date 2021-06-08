<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user_get",methods={"GET"})
     */
    public function afficherUser(
        UserRepository $userRepository
    ): Response {
        $user = $userRepository->findAll();
        return $this->json($user, 200, []);
    }

    /**
     * @Route("/user", name="user_post",methods={"POST"})
     */
    public function ajoutUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        TokenGeneratorInterface $tokenGenerator,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        try {
            $user = new User();
            $data = $request->getContent();
            $dataDeserializer = $serializer->deserialize($data, User::class, 'json');

            $erreurs = $validator->validate($dataDeserializer);
            if (count($erreurs) > 0) {
                return $this->json($erreurs, 400, []);
            } else {
                $dataDeserializer->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $dataDeserializer->getPassword()
                    )
                )
                    ->setRegistrationToken($tokenGenerator->generateToken())
                    ->setRoles([]);
                $entityManager->persist($dataDeserializer);
                $entityManager->flush();
                return $this->json($dataDeserializer, 200, []);
            }
        } catch (NotEncodableValueException $exeption) {
            return $this->json([
                'status' => 500,
                'message' => $exeption->getMessage()
            ], 500, []);
        }
    }

    /**
     * @Route("/user/{id}", name="user_delete",methods={"DELETE"})
     */
    public function supprimertUser(
        $id,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ) {
        // $data = $request->getContent();
        // $dataDeserializer = $serializer->deserialize($data, User::class, 'json');

        $user = $userRepository->findOneBy(['id' => $id]);

        $entityManager->remove($user);

        $entityManager->flush();

        return $this->json([
            "status" => 200,
        ], 200, []);
    }

    /**
     * @Route("/user/{id}", name="user_update",methods={"PUT"})
     */
    public function updatetUser(
        $id,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        try {
            $dataUser = $userRepository->findOneBy(['id' => $id]);

            $user = new User();
            $data = $request->getContent();
            $dataDeserializer = $serializer->deserialize($data, User::class, 'json');

            $erreurs = $validator->validate($dataDeserializer);
            if (count($erreurs) > 0) {
                return $this->json($erreurs, 400, []);
            } else {
                $dataUser->setEmail($dataDeserializer->getEmail())
                    ->setPassword(
                        $passwordEncoder->encodePassword(
                            $user,
                            $dataDeserializer->getPassword()
                        )
                    );
                $entityManager->persist($dataUser);
                $entityManager->flush();
                return $this->json($dataUser, 200, []);
            }
        } catch (NotEncodableValueException $exeption) {
            return $this->json([
                'status' => 500,
                'message' => $exeption->getMessage()
            ], 500, []);
        }
    }
}
