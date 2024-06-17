<?php

namespace GS\Command\Contracts\IO;

use Symfony\Component\Console\Style\SymfonyStyle;

class ErrorIODumper extends AbstractIODumper
{
	//###> ABSTRACT ###
	
	/* AbstractIODumper */
	protected function dump(
		SymfonyStyle &$io,
		mixed $normalizedMessage,
	): void {
		$io->error($normalizedMessage);
	}
	
	//###< ABSTRACT ###
}
