<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downloads_the_invoice_as_a_pdf(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_the_pdf_filename_is_derived_from_the_invoice_number(): void
    {
        $invoice = Invoice::factory()->create(['number' => 'INV-2026-0042']);

        $response = $this->actingAs($this->admin())->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $this->assertStringContainsString('INV-2026-0042', (string) $response->headers->get('content-disposition'));
    }

    public function test_it_emails_the_invoice_to_the_customer(): void
    {
        Mail::fake();

        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

        $this->actingAs($this->admin())
            ->post(route('invoices.send', $invoice))
            ->assertSessionHasNoErrors();

        Mail::assertQueued(
            InvoiceMail::class,
            fn (InvoiceMail $mail): bool => $mail->hasTo($invoice->customer->email)
                && $mail->invoice->is($invoice),
        );
    }

    public function test_the_email_renders_with_the_pdf_attached(): void
    {
        $invoice = Invoice::factory()->create(['number' => 'INV-2026-0007']);

        $mail = new InvoiceMail($invoice);
        $rendered = $mail->render();

        $this->assertStringContainsString('Invoice INV-2026-0007', $rendered);
        $this->assertStringContainsString($invoice->customer->name, $rendered);
        $this->assertStringContainsString($invoice->total->format(), $rendered);
        $this->assertCount(1, $mail->attachments());
    }

    public function test_a_guest_cannot_download_a_pdf(): void
    {
        $invoice = Invoice::factory()->create();

        $this->get(route('invoices.pdf', $invoice))->assertRedirect(route('login'));
    }

    /**
     * Create an admin user.
     */
    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
