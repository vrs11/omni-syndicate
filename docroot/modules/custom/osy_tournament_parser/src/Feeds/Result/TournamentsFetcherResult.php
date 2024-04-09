<?php

namespace Drupal\osy_tournament_parser\Feeds\Result;

use DirectoryIterator;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\Result\FetcherResultInterface;

/**
 * The default fetcher result object.
 */
class TournamentsFetcherResult implements FetcherResultInterface {

  use DependencySerializationTrait;

  /**
   * Constructs a new FetcherResult object.
   *
   * @param string $filesPath
   *   The path to the result files directory.
   */
  public function __construct(
    protected string $filesPath,
    protected array $states,
    protected ?FileSystemInterface $fileSystem = NULL
  ) {
    if (is_null($fileSystem)) {
      $this->fileSystem = \Drupal::service('file_system');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    return $this->getFilePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getFilesList() {
    $this->checkFiles();
    return $this->sanitizeFiles(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilePath() {
    $this->checkFiles();
    return $this->sanitizeFiles();
  }

  /**
   * Checks that a file exists and is readable.
   *
   * @throws \RuntimeException
   *   Thrown if the file isn't readable or writable.
   */
  protected function checkFiles() {
    if (!file_exists($this->filesPath) && !is_dir($this->filesPath)) {
      throw new \RuntimeException(new FormattableMarkup('Directory %filepath does not exist.', ['%filepath' => $this->filesPath]));
    }

    if (!is_readable($this->filesPath)) {
      throw new \RuntimeException(new FormattableMarkup('Directory %filepath is not readable.', ['%filepath' => $this->filesPath]));
    }
  }

  /**
   * Sanitizes the file in place.
   *
   * Currently supported sanitizations:
   * - Remove BOM header from UTF-8 files.
   *
   * @return string|mixed
   *   The file path of the sanitized file.
   *
   * @throws \RuntimeException
   *   Thrown if the file is not writable.
   */
  protected function sanitizeFiles($returnList = FALSE) {
    $list = [];
    $dir = new DirectoryIterator($this->filesPath);
    foreach ($dir as $fileInfo) {
      if ($fileInfo->isDot()) {
        continue;
      }

      $list[] = $fileInfo;
      $file = $fileInfo->getRealPath();
      $handle = fopen($file, 'r');
      $line = fgets($handle);
      fclose($handle);

      // If BOM header is present, read entire contents of file and overwrite the
      // file with corrected contents.
      if (substr($line, 0, 3) !== pack('CCC', 0xef, 0xbb, 0xbf)) {
        continue;
      }

      if (!is_writable($file)) {
        throw new \RuntimeException(new FormattableMarkup('File %filepath is not writable.', ['%filepath' => $file]));
      }

      $contents = file_get_contents($file);
      $contents = substr($contents, 3);
      file_put_contents($file, $contents);
    }

    if ($returnList) {
      return $list;
    }

    return $this->filesPath;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanUp() {
    if ($this->filesPath) {
      $this->fileSystem->unlink($this->filePath);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStates() {
    return $this->states;
  }
}
