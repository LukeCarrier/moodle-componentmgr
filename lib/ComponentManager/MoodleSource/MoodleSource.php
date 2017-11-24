<?php

/**
 * Moodle component manager.
 *
 * @author Luke Carrier <luke@carrier.im>
 * @copyright 2016 Luke Carrier
 * @license GPL-3.0+
 */

namespace ComponentManager\MoodleSource;

use ComponentManager\MoodleVersion;
use Psr\Log\LoggerInterface;

/**
 * Moodle source.
 *
 * Moodle sources describe means of obtaining the Moodle source tree.
 */
interface MoodleSource {
    /**
     * Get Moodle source ID.
     *
     * @return string
     */
    public function getId();

    /**
     * Obtain Moodle.
     *
     * @param MoodleVersion   $moodleVersion
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function obtainMoodle(MoodleVersion $moodleVersion,
                                 LoggerInterface $logger);
}
