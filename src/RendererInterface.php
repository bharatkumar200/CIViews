<?php

/**
 * This file was a part of CodeIgniter 4 framework.
 *
 * I decoupled it from the framework and made some changes.
 */

namespace SimplyDi\CIViews;

/**
 * Interface RendererInterface
 *
 * The interface used for displaying Views and/or theme files.
 */
interface RendererInterface
{
    /**
     * Builds the output based upon a file name and any
     * data that has already been set.
     *
     * @param string $view
     * @param array|null $options Reserved for 3rd-party uses since
     *                        it might be needed to pass additional info
     *                        to other template engines.
     * @param bool $saveData Whether to save data for subsequent calls
     * @return string
     */
    public function render(string $view, ?array $options = null, bool $saveData = false): string;

    /**
     * Builds the output based upon a string and any
     * data that has already been set.
     *
     * @param string $view The view contents
     * @param array|null $options Reserved for 3rd-party uses since
     *                         it might be needed to pass additional info
     *                         to other template engines.
     * @param bool $saveData Whether to save data for subsequent calls
     * @return string
     */
    public function renderString(string $view, ?array $options = null, bool $saveData = false): string;

    /**
     * Sets several pieces of view data at once.
     *
     * @param array $data
     * @param string|null $context The context to escape it for: html, css, js, url
     *                        If 'raw,' no escaping will happen
     *
     * @return RendererInterface
     */
    public function setData(array $data = [], ?string $context = null): RendererInterface;

    /**
     * Sets a single piece of view data.
     *
     * @param string $name
     * @param mixed|null $value
     * @param string|null $context The context to escape it for: html, css, js, url
     *                        If 'raw' no escaping will happen
     *
     * @return RendererInterface
     */
    public function setVar(string $name, mixed $value = null, ?string $context = null): RendererInterface;

    /**
     * Removes all the view data from the system.
     *
     * @return RendererInterface
     */
    public function resetData(): RendererInterface;
}
