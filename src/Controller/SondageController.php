<?php

namespace App\Controller;

use App\Entity\Choix;
use App\Entity\Question;
use App\Entity\Sondage;
use App\Entity\Votant;
use App\Repository\QuestionRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;

class SondageController extends AbstractController
{
    const MAX_LIMIT = 100;

    private $mapRouteParameterToEntityClass = [
        'sondage' => Sondage::class,
        'question' => Question::class,
        'choix' => Choix::class,
        'votant' => Votant::class
    ];

    /**
     * @Route("/{entityType}", name="get_entities", methods={"GET"})
     * @param Request $request
     * @param $entityType
     * @return JsonResponse
     */
    public function index(Request $request, $entityType)
    {
        if (false === array_key_exists($entityType, $this->mapRouteParameterToEntityClass)) {
            return $this->json(['status' => 'failure', 'message' => 'entity '.$entityType.' is not yet managed by this api']);
        }

        $entityClass = $this->mapRouteParameterToEntityClass[$entityType];

        try {
            $objects = $this->parseQueryAndFetch($request->query->all(), $entityClass);
        } catch (\Exception $exception) {
            return $this->json(['status' => 'failure', 'message' => $exception->getMessage()]);
        }


        return $this->json(['status' => 'success', 'data' => $objects]);
    }

    /**
     * @Route("/{entityType}/{id}", name="get_single_entity", methods={"GET"})
     * @param int $id
     * @param $entityType
     * @return JsonResponse
     */
    public function getSondage(int $id, $entityType){

        if (false === array_key_exists($entityType, $this->mapRouteParameterToEntityClass)) {
            return $this->json(['status' => 'failure', 'message' => 'entity '.$entityType.' is not yet managed by this api']);
        }

        $entityClass = $this->mapRouteParameterToEntityClass[$entityType];

        try{
            $sondage = $this->fetch($entityClass, ['id' => $id]);
        }catch (\Exception $exception) {
            return $this->json(['status' => 'failure', 'message' => $exception->getMessage()]);
        }

        return $this->json(['status' => 'success', 'data' => $sondage]);
    }

    /**
     * @Route("/{entityType}/{id}", name="edit_entity", methods={"POST", "PUT"})
     * @param Request $request
     * @param ObjectManager $em
     * @param int $id
     * @return JsonResponse
     */
    public function postSondage(Request $request, ObjectManager $em, $entityType, int $id = null){

        if (false === array_key_exists($entityType, $this->mapRouteParameterToEntityClass)) {
            return $this->json(['status' => 'failure', 'message' => 'entity '.$entityType.' is not yet managed by this api']);
        }

        $entityClass = $this->mapRouteParameterToEntityClass[$entityType];
        $properties = $request->request->all();
        $object = null === $id ? null : $em->find($entityClass, $id);

        try {
            $this->edit($entityClass, $properties, $object);
        } catch (\Exception $exception) {
            return $this->json(['status' => 'failure', 'message' => $exception->getMessage()]);
        }

        return $this->json(['status' => 'success', 'data' => $object]);

    }

    /**
     * @Route("/{entityType}/{id}", name="delete_entity", methods={"DELETE"})
     * @param ObjectManager $manager
     * @param $entityType
     * @param int $id
     * @return JsonResponse
     */
    public function deleteSondage(ObjectManager $manager, $entityType, int $id){

        if (false === array_key_exists($entityType, $this->mapRouteParameterToEntityClass)) {
            return $this->json(['status' => 'failure', 'message' => 'entity '.$entityType.' is not yet managed by this api']);
        }

        $entityClass = $this->mapRouteParameterToEntityClass[$entityType];

        $object = $manager->find($entityClass, $id);

        try {
            $manager->remove($object);
            $manager->flush();
        } catch (\Exception $exception) {
            return $this->json(['status' => 'failure', 'message' => $exception->getMessage()]);
        }

        return $this->json(['status' => 'success', 'data' => 'successfully deleted object']);
    }




    private function parseQueryAndFetch($query, $entityClass){
        $limit = $query['limit'] ?? self::MAX_LIMIT;
        if($limit > self::MAX_LIMIT){
            $limit = self::MAX_LIMIT;
        }
        $first = $query['first'] ?? 0 ;
        $order = $query['order'] ?? [];
        $criteria = [];
        foreach ($query as $property => $value) {
            if (!in_array($property, ['limit', 'first', 'order'])) {
                $criteria[$property] = $value;
            }
        }

        return $this->fetch($entityClass, $criteria, (int) $first, (int) $limit, $order);
    }

    private function edit($entityClass, $properties, $object = null){
        $object = $object ?? new $entityClass();

        $accessor = PropertyAccess::createPropertyAccessor();
        $em = $this->getDoctrine()->getManager();
        $metadata = $em->getClassMetadata($entityClass);

        foreach ($properties as $property => $value) {
            if (!$metadata->hasField($property)) {
                continue;
            }
            if ($metadata->isSingleValuedAssociation($property)) {
                $object = $this->setSingleAssociationValue($property, $metadata->getAssociationTargetClass($property), $value, $object);
            } elseif ($metadata->isCollectionValuedAssociation($property)) {
                $object = $this->setCollectionAssociationValue($property, $metadata->getAssociationTargetClass($property), $value, $object);
            }else{
                $accessor->setValue($object, $property, trim(strip_tags($value)));
            }
        }

        $em->persist($object);
        $em->flush();
    }

    private function setSingleAssociationValue($property, $targetAssociationClass, $targetAssociationId, $entity){
        $em = $this->getDoctrine()->getManager();
        $object = $em->find($targetAssociationClass, $targetAssociationId);
        $accessor = PropertyAccess::createPropertyAccessor();
        if(null === $object){
            throw new \Exception('Associated Object with id '.$targetAssociationId.' for property '.$property.' not found');
        }else{
            $accessor->setValue($entity, $property, $object);
        }
        return $entity;
    }

    private function setCollectionAssociationValue($property, $targetAssociationClass, $targetAssociationIdCollection, $entity){
        $em = $this->getDoctrine()->getManager();
        $accessor = PropertyAccess::createPropertyAccessor();
        $objects = array_map(function (int $id) use ($em, $targetAssociationClass){
            $object = $em->find($targetAssociationClass, $id);
            if(null === $object){
                throw new \Exception('Associated Object with id '.$id.' of class '.$targetAssociationClass.' not found');
            }
            return $object;
        }, $targetAssociationIdCollection);
        $accessor->setValue($entity, $property, $objects);
        return $entity;
    }


    private function fetch($entityClass, $criteria, int $first = null, int $limit = null, array $order = []){

        $em = $this->getDoctrine()->getManager();

        /** @var EntityRepository $repository */
        $repository = $em->getRepository($entityClass);
        $metadata = $em->getClassMetadata($entityClass);

        $qb = $repository->createQueryBuilder('e');
        $qb->setFirstResult($first ?? 0);
        $qb->setMaxResults($limit ?? self::MAX_LIMIT);
        foreach ($order as $property => $direction) {
            $qb->addOrderBy($property, $direction);
        }

        $scalarFields = array_filter($metadata->getFieldNames(), function ($field) use ($metadata){
            return !$metadata->hasAssociation($field);
        });

        foreach ($criteria as $criterion => $value) {
            if ($metadata->hasField($criterion) && !$metadata->hasAssociation($criterion)) {
                if($criterion === 'search'){
                    $searchStatements = array_map(function ($field) use ($qb) {
                        return $qb->expr()->like('e.'.$field, ':search');
                    }, $scalarFields);
                    $qb->andWhere(...$searchStatements)->setParameter('search', $value);
                }else{
                    $qb->andWhere('e.'.$criterion.'= :'.$criterion)->setParameter(':'.$criterion, $criterion);
                }
            }
        }

        return $qb->getQuery()->getResult();
    }
    
}

