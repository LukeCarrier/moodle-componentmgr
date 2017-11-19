<?php

/**
 * Moodle component manager.
 *
 * @author Luke Carrier <luke@carrier.im>
 * @copyright 2016 Luke Carrier
 * @license GPL-3.0+
 */

namespace ComponentManager\PackageRepository;

use ComponentManager\Component;
use ComponentManager\ComponentSource\GitComponentSource;
use ComponentManager\ComponentSpecification;
use ComponentManager\ComponentVersion;

/**
 * Git package repository.
 *
 * Allows sourcing components from arbitrary Git repositories.
 */
class GitPackageRepository extends AbstractCachingPackageRepository
        implements PackageRepository {
    /**
     * @override PackageRepository
     */
    public function getId() {
        return 'Git';
    }

    /**
     * @override PackageRepository
     */
    public function getName() {
        return 'Git package repository';
    }

    /**
     * @override PackageRepository
     */
    public function getComponent(ComponentSpecification $componentSpecification) {
        return new Component($componentSpecification->getName(), [
            new ComponentVersion(null, null, null, [
                new GitComponentSource($componentSpecification->getExtra('uri'), $componentSpecification->getVersion())
            ]),
        ], $this);
    }

    /**
     * @override PackageRepository
     */
    public function satisfiesVersion($versionSpecification, ComponentVersion $version) {
        return true;
    }
}
