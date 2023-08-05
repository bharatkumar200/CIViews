<?php

/**
 * This file was a part of CodeIgniter 4 framework.
 *
 * I decoupled it from the framework and made some changes.
 */

namespace SimplyDi\CIViews;

use Exception;
use RuntimeException;

/**
 * Class View
 */
class Renderer implements RendererInterface
{
    /**
     * Data that is made available to the Views.
     *
     * @var array
     */
    public array $data = [];

    protected array|true $saveData = [];

    /**
     * Merge savedData and userData
     */
    protected $tempData;

    /**
     * The base directory to look in for our Views.
     *
     * @var string
     */
    protected string $viewPath;

    /**
     * The render variables
     *
     * @var array
     */
    protected array $renderVars = [];

    /**
     * The name of the layout being used, if any.
     * Set by the `extend` method used within views.
     *
     * @var string|null
     */
    protected ?string $layout;

    /**
     * Holds the sections and their data.
     *
     * @var array
     */
    protected array $sections = [];

    /**
     * The name of the current section being rendered,
     * if any.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected ?string $currentSection;

    /**
     * The name of the current section being rendered,
     * if any.
     *
     * @var array<string>
     */
    protected array $sectionStack = [];

    private string $fileExtension;

    public function __construct(?string $viewPath = null, string $fileExtension = ".php", bool $saveData = true)
    {
        $this->viewPath = rtrim($viewPath, '\\/ ') . DIRECTORY_SEPARATOR;
        $this->saveData = $saveData;
        $this->fileExtension = $fileExtension;
        $this->layout = null; // Initialize the $layout property
    }

    /**
     * Builds the output based upon a file name and any
     * data that has already been set.
     *
     * @param string $view File name of the view source
     * @param array|null $options Reserved for 3rd-party uses since
     *                             it might be needed to pass additional info
     *                             to other template engines.
     * @param bool|null $saveData If true, saves data for subsequent calls,
     *                             if false, cleans the data after displaying,
     *                             if null, uses the config setting.
     * @throws Exception
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $this->renderVars['start'] = microtime(true);

        // Store the results here, so even if
        // multiple views are called in a view, it won't
        // clean it unless we mean it to.
        $saveData ??= $this->saveData;

        $fileExt = pathinfo($view, PATHINFO_EXTENSION);
        // allow Views as .html, .tpl, etc (from CI3)
        $this->renderVars['view'] = empty($fileExt) ? $view . $this->fileExtension : $view;

        $this->renderVars['options'] = $options ?? [];

        $this->renderVars['file'] = $this->viewPath . $this->renderVars['view'];

        if (!is_file($this->renderVars['file'])) {
            $this->renderVars['file'] = $this->locateFile(
                $this->renderVars['view'],
                empty($fileExt) ? 'php' : $fileExt
            );
        }

        // locateFile will return an empty string if the file cannot be found.
        if (empty($this->renderVars['file'])) {
            throw new Exception("template not found" . $this->renderVars['view']);
        }

        // Make our view data available to the view.
        $this->prepareTemplateData($saveData);

        // Save current vars
        $renderVars = $this->renderVars;

        $output = (function (): string {
            extract($this->tempData);
            ob_start();
            include $this->renderVars['file'];

            return ob_get_clean() ?: '';
        })();

        // Get back current vars
        $this->renderVars = $renderVars;

        // When using layouts, the data has already been stored
        // in $this->sections, and no other valid output
        // is allowed in $output, so we'll overwrite it.
        if ($this->layout !== null && $this->sectionStack === []) {
            $layoutView = $this->layout;
            $this->layout = null;
            // Save current vars
            $renderVars = $this->renderVars;
            $output = $this->render($layoutView, $options, $saveData);
            // Get back current vars
            $this->renderVars = $renderVars;
        }

        $this->tempData = null; // Clear the temp data since display has happened here

        return $output;
    }

    /**
     * Builds the output based upon a string and any
     * data that has already been set.
     * Cache does not apply, because there is no "key".
     *
     * @param string $view The view contents
     * @param array|null $options Reserved for 3rd-party uses since
     *                             it might be needed to pass additional info
     *                             to other template engines.
     * @param bool|null $saveData If true, saves data for subsequent calls,
     *                             if false, cleans the data after displaying,
     *                             if null, uses the config setting.
     */
    public function renderString(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $saveData ??= $this->saveData;
        $this->prepareTemplateData($saveData);

        $output = (function (string $view): string {
            extract($this->tempData);
            ob_start();
            eval('?>' . $view);

            return ob_get_clean() ?: '';
        })($view);

        $this->tempData = null;

        return $output;
    }

    /**
     * Extract the first bit of a long string and add ellipsis
     */
    public function excerpt(string $string, int $length = 20): string
    {
        return (strlen($string) > $length) ? substr($string, 0, $length - 3) . '...' : $string;
    }

    /**
     * Sets several pieces of view data at once.
     *
     * @param string|null $context The context to escape it for: html, css, js, url
     *                             If null, no escaping will happen
     * @phpstan-param null|'html'|'js'|'css'|'url'|'attr'|'raw' $context
     */
    public function setData(array $data = [], ?string $context = null): RendererInterface
    {
        if ($context) {
            $data = $this->esc($data, $context);
        }

        $this->tempData ??= $this->data;
        $this->tempData = array_merge($this->tempData, $data);

        return $this;
    }

    /**
     * Sets a single piece of view data.
     *
     * @param mixed|null $value
     * @param string|null $context The context to escape it for: html, css, js, url
     *                             If null, no escaping will happen
     * @phpstan-param null|'html'|'js'|'css'|'url'|'attr'|'raw' $context
     */
    public function setVar(string $name, mixed $value = null, ?string $context = null): RendererInterface
    {
        if ($context) {
            $value = $this->esc($value, $context);
        }

        $this->tempData ??= $this->data;
        $this->tempData[$name] = $value;

        return $this;
    }

    /**
     * Removes all the view data from the system.
     */
    public function resetData(): RendererInterface
    {
        $this->data = [];

        return $this;
    }

    /**
     * Returns the current data that will be displayed in the view.
     */
    public function getData(): array
    {
        return $this->tempData ?? $this->data;
    }

    /**
     * Specifies that the current view should extend an existing layout.
     */
    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Starts holds content for a section within the layout.
     *
     * @param string $name Section name
     */
    public function section(string $name): void
    {
        // Saved to prevent BC
        $this->sectionStack[] = $name;

        ob_start();
    }

    /**
     * Captures the last section
     *
     * @throws RuntimeException
     */
    public function endSection(): void
    {
        $contents = ob_get_clean();

        if ($this->sectionStack === []) {
            throw new RuntimeException('View themes, no current section.');
        }

        $section = array_pop($this->sectionStack);

        // Ensure an array exists so we can store multiple entries for this.
        if (!array_key_exists($section, $this->sections)) {
            $this->sections[$section] = [];
        }

        $this->sections[$section][] = $contents;
    }

    /**
     * Renders a section's contents.
     */
    public function renderSection(string $sectionName): void
    {
        if (!isset($this->sections[$sectionName])) {
            echo '';

            return;
        }

        foreach ($this->sections[$sectionName] as $key => $contents) {
            echo $contents;
            unset($this->sections[$sectionName][$key]);
        }
    }

    /**
     * Used within layout views to include additional views.
     *
     * @param string $view
     * @param array|null $options
     * @param bool $saveData
     * @return string
     */
    public function include(string $view, ?array $options = null, bool $saveData = true): string
    {
        return $this->render($view, $options, $saveData);
    }

    protected function prepareTemplateData(bool $saveData): void
    {
        $this->tempData ??= $this->data;

        if ($saveData) {
            $this->data = $this->tempData;
        }
    }

    /**
     * @throws Exception
     */
    private function locateFile(string $view, array|string $param): string
    {
        $file = $this->viewPath . $view . $this->fileExtension;

        if (!is_file($file)) {
            throw new Exception("View file not found: $file");
        }

        return $file;
    }

    /**
     * escape
     */
    private function esc(mixed $value, string $context): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
