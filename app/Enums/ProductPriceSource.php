<?php

namespace App\Enums;

enum ProductPriceSource: string
{
	case MANUFACTURER = 'manufacturer';
	case PROVIDER = 'provider';
	case WAREHOUSE = 'warehouse';

	public static function titles () {
		$result = [];

		foreach (self::cases() as $type) {
			$result[$type->value] = $type->title();
		}

		return $result;
	}

	public function title (): string {
		return match ($this) {
			self::MANUFACTURER => 'Производитель',
			self::PROVIDER     => 'Поставщик',
			self::WAREHOUSE    => 'Свой склад',
		};
	}
}