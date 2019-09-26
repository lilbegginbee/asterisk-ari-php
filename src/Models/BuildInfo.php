<?php

/**
 * The JSONMapper library needs the full name path of
 * a class, so there are no imports used instead.
 *
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

/** @copyright 2019 ng-voice GmbH */

declare(strict_types=1);

namespace NgVoice\AriClient\Models;


/**
 * Info about how Asterisk was built.
 *
 * @package NgVoice\AriClient\Models
 *
 * @author Lukas Stermann <lukas@ng-voice.com>
 */
final class BuildInfo implements ModelInterface
{
    /**
     * @var string Kernel version Asterisk was built on.
     */
    private $kernel;

    /**
     * @var string Machine architecture (x86_64, i686, ppc, etc.).
     */
    private $machine;

    /**
     * @var string Username that built Asterisk.
     */
    private $user;

    /**
     * @var string Date and time when Asterisk was built.
     */
    private $date;

    /**
     * @var string OS Asterisk was built on.
     */
    private $os;

    /**
     * @var string Compile time options, or empty string if default.
     */
    private $options;

    /**
     * @return string
     */
    public function getKernel(): string
    {
        return $this->kernel;
    }

    /**
     * @param string $kernel
     */
    public function setKernel(string $kernel): void
    {
        $this->kernel = $kernel;
    }

    /**
     * @return string
     */
    public function getMachine(): string
    {
        return $this->machine;
    }

    /**
     * @param string $machine
     */
    public function setMachine(string $machine): void
    {
        $this->machine = $machine;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getOs(): string
    {
        return $this->os;
    }

    /**
     * @param string $os
     */
    public function setOs(string $os): void
    {
        $this->os = $os;
    }

    /**
     * @return string
     */
    public function getOptions(): string
    {
        return $this->options;
    }

    /**
     * @param string $options
     */
    public function setOptions(string $options): void
    {
        $this->options = $options;
    }
}
