<?php

namespace App\Services;

class Gosnumber
{
	public function __construct (
		private string $original = "",
		private string $number = "",
	) {
	}

	public function parse (?string $number): self {
		if ($number !== null) {
			$this->original = $number;
			$this->parseOriginal();
		}
		return $this;
	}

	private function parseOriginal () {
		$this->number = $this->original;
		$this->toLower();
		$this->toEng();
		$this->clean();
	}

	public function clean (): self {
		$this->number = preg_replace("/[^0-9abekmhopctyx]/", "", $this->number);
		return $this;
	}

	public function __toString (): string {
		return $this->number;
	}

	public function toUpper (): self {
		$this->number = mb_strtoupper($this->number);
		return $this;
	}

	public function toLower (): self {
		$this->number = mb_strtolower($this->number);
		return $this;
	}

	public function toRus (): self {

		$this->number = str_replace("a", "а", $this->number);
		$this->number = str_replace("b", "в", $this->number);
		$this->number = str_replace("e", "е", $this->number);
		$this->number = str_replace("k", "к", $this->number);
		$this->number = str_replace("m", "м", $this->number);
		$this->number = str_replace("h", "н", $this->number);
		$this->number = str_replace("o", "о", $this->number);
		$this->number = str_replace("p", "р", $this->number);
		$this->number = str_replace("c", "с", $this->number);
		$this->number = str_replace("t", "т", $this->number);
		$this->number = str_replace("y", "у", $this->number);
		$this->number = str_replace("x", "х", $this->number);

		$this->number = str_replace("A", "а", $this->number);
		$this->number = str_replace("B", "В", $this->number);
		$this->number = str_replace("E", "Е", $this->number);
		$this->number = str_replace("K", "К", $this->number);
		$this->number = str_replace("M", "М", $this->number);
		$this->number = str_replace("H", "Н", $this->number);
		$this->number = str_replace("O", "О", $this->number);
		$this->number = str_replace("P", "Р", $this->number);
		$this->number = str_replace("C", "С", $this->number);
		$this->number = str_replace("T", "Т", $this->number);
		$this->number = str_replace("Y", "У", $this->number);
		$this->number = str_replace("X", "Х", $this->number);

		return $this;
	}

	public function toEng (): self {

		$this->number = str_replace("а", "a", $this->number);
		$this->number = str_replace("в", "b", $this->number);
		$this->number = str_replace("е", "e", $this->number);
		$this->number = str_replace("к", "k", $this->number);
		$this->number = str_replace("м", "m", $this->number);
		$this->number = str_replace("н", "h", $this->number);
		$this->number = str_replace("о", "o", $this->number);
		$this->number = str_replace("р", "p", $this->number);
		$this->number = str_replace("с", "c", $this->number);
		$this->number = str_replace("т", "t", $this->number);
		$this->number = str_replace("у", "y", $this->number);
		$this->number = str_replace("х", "x", $this->number);

		$this->number = str_replace("а", "A", $this->number);
		$this->number = str_replace("В", "B", $this->number);
		$this->number = str_replace("Е", "E", $this->number);
		$this->number = str_replace("К", "K", $this->number);
		$this->number = str_replace("М", "M", $this->number);
		$this->number = str_replace("Н", "H", $this->number);
		$this->number = str_replace("О", "O", $this->number);
		$this->number = str_replace("Р", "P", $this->number);
		$this->number = str_replace("С", "C", $this->number);
		$this->number = str_replace("Т", "T", $this->number);
		$this->number = str_replace("У", "Y", $this->number);
		$this->number = str_replace("Х", "X", $this->number);

		return $this;
	}

	public function valid (): bool {
		return (bool)preg_match("/[a-z]{1}[0-9]{3}[a-z]{2}[0-9]{2,3}/", $this->number);
	}

	public function get (): string {
		return $this->number;
	}

	public function original (): string {
		return $this->original;
	}
}