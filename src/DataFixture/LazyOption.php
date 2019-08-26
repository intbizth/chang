<?php

declare(strict_types=1);

namespace Chang\DataFixture;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Webmozart\Assert\Assert;

/**
 * Using the hacky hack to distinct between option which wasn't set
 * and option which was set to empty.
 *
 * Usage:
 *
 *   $optionsResolver
 *     ->setDefault('option', LazyOption::randomOne($repository))
 *     ->setNormalizer('option', LazyOption::findOneBy($repository, 'code'))
 *   ;
 *
 *   Returns:
 *     - null if user explicitly set it (['option' => null])
 *     - random one if user skipped that option ([])
 *     - specific one if user defined that option (['option' => 'CODE'])
 */
class LazyOption
{
    /**
     * @param RepositoryInterface $repository
     *
     * @return \Closure
     */
    public static function randomOne(RepositoryInterface $repository): \Closure
    {
        return function (Options $options) use ($repository) {
            $objects = $repository->findAll();

            if ($objects instanceof Collection) {
                $objects = $objects->toArray();
            }

            Assert::notEmpty($objects);

            return $objects[array_rand($objects)];
        };
    }

    /**
     * @param RepositoryInterface $repository
     * @param int $chanceOfRandomOne
     *
     * @return \Closure
     */
    public static function randomOneOrNull(RepositoryInterface $repository, int $chanceOfRandomOne): \Closure
    {
        return function (Options $options) use ($repository, $chanceOfRandomOne) {
            if (mt_rand(1, 100) > $chanceOfRandomOne) {
                return null;
            }

            $objects = $repository->findAll();

            if ($objects instanceof Collection) {
                $objects = $objects->toArray();
            }

            return 0 === count($objects) ? null : $objects[array_rand($objects)];
        };
    }

    /**
     * @param RepositoryInterface $repository
     * @param int $amount
     *
     * @return \Closure
     */
    public static function randomOnes(RepositoryInterface $repository, int $amount): \Closure
    {
        return function (Options $options) use ($repository, $amount) {
            $objects = $repository->findAll();

            if ($objects instanceof Collection) {
                $objects = $objects->toArray();
            }

            $selectedObjects = [];
            for (; $amount > 0 && count($objects) > 0; --$amount) {
                $randomKey = array_rand($objects);

                $selectedObjects[] = $objects[$randomKey];

                unset($objects[$randomKey]);
            }

            return $selectedObjects;
        };
    }

    /**
     * @param RepositoryInterface $repository
     *
     * @return \Closure
     */
    public static function all(RepositoryInterface $repository): \Closure
    {
        return function (Options $options) use ($repository) {
            return $repository->findAll();
        };
    }

    /**
     * @param RepositoryInterface $repository
     * @param string $field
     *
     * @return \Closure
     */
    public static function findBy(RepositoryInterface $repository, string $field): \Closure
    {
        return function (Options $options, $previousValues) use ($repository, $field) {
            if (null === $previousValues || [] === $previousValues) {
                return $previousValues;
            }

            Assert::isArray($previousValues);

            $resources = [];
            foreach ($previousValues as $previousValue) {
                if (is_object($previousValue)) {
                    $resources[] = $previousValue;
                } else {
                    $resources[] = $repository->findOneBy([$field => $previousValue]);
                }
            }

            return $resources;
        };
    }

    /**
     * @param RepositoryInterface $repository
     * @param string $field
     *
     * @return \Closure
     */
    public static function findOneBy(RepositoryInterface $repository, string $field): \Closure
    {
        return function (Options $options, $previousValue) use ($repository, $field) {
            if (null === $previousValue || [] === $previousValue) {
                return $previousValue;
            }

            if (is_object($previousValue)) {
                return $previousValue;
            }

            return $repository->findOneBy([$field => $previousValue]);
        };
    }
}
