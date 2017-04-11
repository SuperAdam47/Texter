<?php
namespace Texter\extensions;

use Texter\Main;

/**
 * TexterExtension
 */
interface TexterExtension{
  /* Variable inheritance */
  public function __construct(Main $main);

  /* initialize(To display first load message) */
  public function initialize();

  /* @return filestream or null */
  public function getResource(string $filename);

  /* @return string dir path */
  public function getDir(): string;

  /* @return string version */
  public function getVersion(): string;

  /* @return string name */
  public function getName(): string;

  /* Used to distinguish malicious files */
  public function texter();
}
