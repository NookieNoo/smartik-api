@component("mail::message")

# Принят и успешно обработан файл от email интеграции

Партнёр: **{{$provider}}**<br />
Дата: **{{$date}}**<br />
Тип: **{{$typeName}}**

@if($isPrices)

@if(count($report['not_found'] ?? []))
## Не найдены
@component('mail::table')
| ШК       |
| :--------- |
@foreach ($report['not_found'] as $item)
| {{$item}} |
@endforeach
@endcomponent
@endif

@if(count($report['success'] ?? []))
## Успешные
@component('mail::table')
| ШК       | ДП         | СГ         | Количество         | РРЦ         | Цена         |
| :--------- | :------------- | :------------- | :------------- | :------------- | :------------- |
@foreach ($report['success'] as $item)
| {{$item['ean']}} | {{$item['manufactured_at']}} | {{$item['expired_at']}} | {{$item['count']}} | {{$item['rrc']}} | {{$item['finish_price']}} |
@endforeach
@endcomponent
@endif

@endif


Хорошего дня, команда [Смартик](https://smartik.me)

@endcomponent