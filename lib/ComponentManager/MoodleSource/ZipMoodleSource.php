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

class ZipMoodleSource implements MoodleSource {
    /**
     * @inheritdoc MoodleSource
     */
    public function getId() {
        return 'Zip';
    }

    /**
     * @inheritdoc MoodleSource
     */
    public function obtainMoodle(MoodleVersion $moodleVersion,
                                 LoggerInterface $logger) {
        $uri = $moodleVersion->getDownloadUri();

        $logger->info('Downloading Moodle', [
            'uri'     => $uri,
            'archive' => $this->archive,
        ]);
    }
}
