<?php

namespace App\Http\Utils;

use App\Jobs\ProcessChunkJob;
use App\Models\CredentialFile;


class Filepatterns
{
    public $num_lines = 0;
    public $log_format = null;

	public function verifyIsValidArchive($filePath)
	{
        $LOG_FORMAT_COMBOLIST = 'combolist';
        $LOG_FORMAT_BOTNET = 'botnet';

		try {
			$handle = fopen($filePath, 'r');
			if (!$handle) {
				throw new \Exception("Não foi possível abrir o arquivo: $filePath");
			}

			$linesToCheck = 20;
			$advancedFormat = false;
			$simpleSeparator = 0;

			for ($i = 0; $i < $linesToCheck && !feof($handle); $i++) {
				$line = trim(fgets($handle));

                if ($this->is_header_or_decorative($line)) {
                    // Ignora linhas de cabeçalho ou decorativas
                    $i--;
                    continue;
                }

				// Verifica se a linha começa com 'URL:'
				if (str_starts_with($line, 'URL:')) {
					// Armazena as três linhas subsequentes para verificação
					$nextLines = [];
					for ($j = 0; $j < 3 && !feof($handle); $j++) {
						$nextLine = trim(fgets($handle));
						$nextLines[] = $nextLine;
					}

					// Verifica se as próximas linhas são Username, Password, Application
					$advancedFormat = (
						(str_starts_with($nextLines[0] ?? '', 'Username:')) &&
						(str_starts_with($nextLines[1] ?? '', 'Password:')) &&
						(str_starts_with($nextLines[2] ?? '', 'Application:'))
					);

					if ($advancedFormat) {
						break;
					}
				}

                $separators = [':',',',';','::',"\t",' '];
                foreach ($separators as $separator) {
                    if (str_contains($line, $separator)) {
                        $parts = explode($separator, $line, 2);
                        if (count($parts) === 2) { // Garante que há exatamente duas partes
                            list($access, $password) = $parts;
                            if (!empty($access) && !empty($password)) {
                                $simpleSeparator++;
                                break;
                            }
                        }
                    }
                }
			}

			fclose($handle);

			if ($simpleSeparator === $linesToCheck) {
                $this->log_format = $LOG_FORMAT_COMBOLIST;
				return ['simpleSeparator' => true];
			} elseif ($advancedFormat) {
                $this->log_format = $LOG_FORMAT_BOTNET;
				return ['avancedFormat' => true];
			} else {
				return ['withoutpatterns' => true, 'filePath' => $filePath];
			}
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}


    public function processFileSimpleSeparator($filePath, $archiveId)
    {
        $chunkSize = env('CHUNK_SIZE', 1000);
        $handle = fopen($filePath, 'r');
        $chunk = [];
        $lineNumber = 0;
        $now = now(); // Gere a data uma vez fora do loop
        $previousSeparator = ':'; // Inicializa com um separador padrão
        $separators = [':',',',';','::',"\t",' ']; // Lista de separadores suportados

        try {
            while (!feof($handle)) {
                $line = fgets($handle);
                $this->num_lines++;

                if ($line) {
                    $line = trim($line);

                    $currentSeparator = $previousSeparator; // Tenta com o separador anterior

                    if (!str_contains($line, $currentSeparator)) {
                        // Busca um separador alternativo caso o atual não esteja presente
                        foreach ($separators as $separator) {
                            if (str_contains($line, $separator)) {
                                $currentSeparator = $separator;
                                break;
                            }
                        }
                    }

                    // Verifica se a linha contém o separador atual
                    if (str_contains($line, $currentSeparator)) {
                        $parts = explode($currentSeparator, $line, 2);

                        // Se a linha está no formato esperado (access:password)
                        if (count($parts) == 2) {
                            list($access, $password) = $parts;

                            $chunk[] = [
                                'access' => $this->covertToUTF8($access),
                                'password' => $this->covertToUTF8($password),
                                'credential_file_id' => $archiveId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];

                            $previousSeparator = $currentSeparator; // Atualiza o separador para a próxima linha
                        }
                    }

                    $lineNumber++;

                    // Despacha o job quando o chunk estiver cheio e limpa o array
                    if ($lineNumber % $chunkSize == 0) {
                        ProcessChunkJob::dispatch($chunk);
                        $chunk = []; // Limpa o array para liberar memória
                        gc_collect_cycles(); // Força a coleta de lixo
                    }
                }
            }

            // Despacha o último chunk se sobrar algum
            if (!empty($chunk)) {
                ProcessChunkJob::dispatch($chunk);
            }

            fclose($handle);
        } catch (\Exception $e) {
            echo "Erro: " . $e->getMessage();
        }
    }

    # padrão antigo Stanley
    public function processFileColon($filePath, $archiveId)
	{
		$chunkSize = env('CHUNK_SIZE', 1000);
		$handle = fopen($filePath, 'r');
		$chunk = [];
		$lineNumber = 0;
		$now = now(); // Gere a data uma vez fora do loop
		try {
			while (!feof($handle)) {
				$line = fgets($handle);
                $this->num_lines++;

				if ($line) {
					$line = trim($line);

					// Verifica se a linha está no formato "access:password"
					if (str_contains($line, ':')) {
						$parts = explode(':', $line);

						// Se a linha está no formato "access:password"
						if (count($parts) == 2) {
							list($access, $password) = $parts;

							// Processa no formato "access:password"
							$chunk[] = [
								'access' => $this->covertToUTF8($access),
								'password' => $this->covertToUTF8($password),
								'credential_file_id' => $archiveId,
								'created_at' => $now,
								'updated_at' => $now,
							];
						} else {
							// Assumindo que o formato é "key: value" (last_name, first_name, email)
							$data = [];

							foreach ($parts as $part) {
								$pair = explode(':', $part, 2);
								if (count($pair) == 2) {
									$key = trim($pair[0]);
									$value = trim($pair[1]);
									$data[$key] = $value;
								}
							}

							// Se existir a chave "email" ou outra chave indicativa de "good logs"
							if (isset($data['email'])) {
								$data['credential_file_id'] = $archiveId;
								$data['created_at'] = $now;
								$data['updated_at'] = $now;
								$chunk[] = $data;
							}
						}
					}

					$lineNumber++;

					// Despacha o job quando o chunk estiver cheio e limpa o array
					if ($lineNumber % $chunkSize == 0) {
						ProcessChunkJob::dispatch($chunk);
						$chunk = []; // Limpa o array para liberar memória
						gc_collect_cycles(); // Força a coleta de lixo
					}
				}
			}

			// Despacha o último chunk se sobrar algum
			if (!empty($chunk)) {
				ProcessChunkJob::dispatch($chunk);
			}

			fclose($handle);
		} catch (\Exception $e) {
			echo "Erro: " . $e->getMessage();
		}
	}

	public function processGoodLogsInChunks($filePath, $archiveId)
	{
		$chunkSize = env('CHUNK_SIZE', 1000);
		$lineNumber = 0;
		$chunk = [];
		$now = now(); // Gere a data uma vez fora do loop

		try {
			$handle = fopen($filePath, 'r');
			if (!$handle) {
				throw new \Exception("Não foi possível abrir o arquivo: $filePath");
			}

			while (!feof($handle)) {
				$line = trim(fgets($handle));

				if (strpos($line, 'URL:') === 0) {
					$data = [
						'url' => trim(substr($line, 4)),
					];

					// Processa as próximas 3 linhas para capturar username, password e application
					for ($i = 0; $i < 3; $i++) {
						$line = trim(fgets($handle));

						if (strpos($line, 'Username:') === 0) {
							$data['access'] = $this->covertToUTF8(trim(substr($line, 9)));
						} elseif (strpos($line, 'Password:') === 0) {
							$data['password'] = $this->covertToUTF8(trim(substr($line, 9)));
						} elseif (strpos($line, 'Application:') === 0) {
							$data['application'] = trim(substr($line, 12));
						}
					}

					$data['credential_file_id'] = $archiveId;
					$data['created_at'] = $now;
					$data['updated_at'] = $now;
					$chunk[] = $data;
					$lineNumber++;

					// Despacha o job quando o chunk estiver cheio e limpa o array
					if ($lineNumber % $chunkSize == 0) {
						ProcessChunkJob::dispatch($chunk);
						$chunk = []; // Limpa o array para liberar memória
						gc_collect_cycles(); // Força a coleta de lixo
					}
				}
			}

			// Despacha o último chunk se sobrar algum
			if (!empty($chunk)) {
				ProcessChunkJob::dispatch($chunk);
			}
		} catch (\Exception $e) {
			echo "Erro: " . $e->getMessage();
		} finally {
			if (isset($handle)) {
				fclose($handle);
			}
		}
	}

	public function insertByDefault($resultFile, $filePath, $credentialId)
	{
        if (isset($resultFile['avancedFormat'])) {
			$this->processGoodLogsInChunks($filePath, $credentialId);
		} elseif (isset($resultFile['simpleSeparator'])) {
			$this->processFileSimpleSeparator($filePath, $credentialId);
		} else {
			return response()->json(['message' => 'O arquivo não está no formato correto.'], 400);
		}

        $credentialFile = CredentialFile::find($credentialId);
        $credentialFile->update(['log_format' => $this->log_format, 'num_lines' => $this->num_lines]);
        return response()->json(['message' => 'Arquivo está sendo processado em fila.'], 201);
	}

	public function covertToUTF8($string)
	{
		if (!mb_check_encoding($string, 'UTF-8')) {
			return $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
		}
		return $string;
	}

    public function is_header_or_decorative(string $line): bool{
        // Remove espaços em branco no início e fim
        $trimmedLine = trim($line);

        // 1) Linhas em branco
        if (strlen($trimmedLine) === 0) {
            return true;
        }

        // 2) Linhas muito curtas
        if (strlen($trimmedLine) < 10) {
            return true;
        }

        // 3) Linhas que começam com '*' ou '#'
        //    Verifica se o primeiro caractere é '*' ou '#'
        if ($trimmedLine[0] === '*' || $trimmedLine[0] === '#') {
            return true;
        }

        // 4) Testa se começa com 3 ou mais espaços
        if (preg_match('/^\s{3,}/', $line)) {
            return true;
        }

        // 5) Linhas com muitos caracteres repetidos, ex.: "========", "**********", etc.
        //    Aqui usamos a mesma regex: /^[\/*\-_=+\\| ]{10,}$/
        if (preg_match('/^[\/*\-_=+\\| ]{10,}$/', $trimmedLine)) {
            return true;
        }

        // 6) Poucos caracteres alfanuméricos:
        //    Se a linha tem mais de 10 caracteres, mas a proporção de caracteres alfanuméricos é < 0.3
        if (strlen($line) > 10) {
            $countAlnum = 0;
            for ($i = 0; $i < strlen($line); $i++) {
                if (ctype_alnum($line[$i])) {
                    $countAlnum++;
                }
            }
            $ratio = $countAlnum / strlen($line);
            if ($ratio < 0.3) {
                return true;
            }
        }

        // Se nenhuma das condições acima foi atendida, retorna falso
        return false;
    }

}
