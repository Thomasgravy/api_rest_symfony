<?php

// src/DataPersister

namespace App\DataPersister;

use App\Entity\Tag;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;
use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;

/**
 *
 */
class ArticleDataPersister implements ContextAwareDataPersisterInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $_entityManager;

    /**
     * @param SluggerInterface
     */
    private $_slugger;

    /**
     * @param Request
     */
    private $_request;

    public function __construct(
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        RequestStack $request
    ) {
        $this->_entityManager = $entityManager;
        $this->_slugger = $slugger;
        $this->_request = $request->getCurrentRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        return $data instanceof Article;
    }

    /**
     * @param Article $data
     */
    public function persist($data, array $context = [])
    {
        $data->setSlug(
            $this
                ->_slugger
                ->slug(strtolower($data->getTitle())). '-' .uniqid()
        );

        // Set the updatedAt value if it's not a POST request
        if ($this->_request->getMethod() !== 'POST') {
            $data->setUpdatedAt(new \DateTime());
        }

        $tagRepository = $this->_entityManager->getRepository(Tag::class);
        foreach ($data->getTags() as $tag) {
            $t = $tagRepository->findOneByLabel($tag->getLabel());

            // if the tag exists, don't persist it
            if ($t !== null) {
                $data->removeTag($tag);
                $data->addTag($t);
            } else {
                $this->_entityManager->persist($tag);
            }
        }

        $this->_entityManager->persist($data);
        $this->_entityManager->flush();
    }

    public function remove($data, array $context = [])
    {
        $this->_entityManager->remove($data);
        $this->_entityManager->flush();
    }
}