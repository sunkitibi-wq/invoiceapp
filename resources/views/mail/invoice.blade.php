@component('mail::message')
# Invoice {{ $invoice->number }}

Hello {{ $customer->name }},

Please find invoice **{{ $invoice->number }}** attached, dated {{ $invoice->issue_date->format('j M Y') }} and due {{ $invoice->due_date->format('j M Y') }}.

<x-mail::panel>
| | |
|:--|:--|
| **Total** | {{ $invoice->total->format() }} |
| **Amount due** | {{ $invoice->balance->format() }} |
| **Due date** | {{ $invoice->due_date->format('j M Y') }} |
</x-mail::panel>

@if ($invoice->notes)
{{ $invoice->notes }}
@endif

@if ($invoice->terms)
{{ $invoice->terms }}
@endif

Thanks,<br>
{{ $from['name'] }}
@endcomponent
