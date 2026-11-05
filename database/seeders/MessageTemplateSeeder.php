<?php

namespace Database\Seeders;

use App\Domain\Notifications\Customer\MessageEvent as E;
use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/**
 * Plantillas iniciales (editables en Ajustes > Notificaciones > Plantillas).
 * WhatsApp: el texto es el que hay que registrar en Meta con el mismo nombre; {{1}}, {{2}}…
 * corresponden, en orden, a las variables de "whatsapp_params".
 */
class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $event => $locales) {
            foreach ($locales as $locale => [$subject, $body]) {
                $params = $this->paramsOf($body);
                MessageTemplate::query()->firstOrCreate(
                    ['event' => $event, 'channel' => 'mail', 'locale' => $locale],
                    ['subject' => $subject, 'body' => $body],
                );
                MessageTemplate::query()->firstOrCreate(
                    ['event' => $event, 'channel' => 'whatsapp', 'locale' => $locale],
                    [
                        'body' => $body,
                        'whatsapp_template' => 'olympo_'.$event,
                        'whatsapp_language' => $locale === 'es' ? 'es' : 'pt_BR',
                        'whatsapp_params' => $params,
                    ],
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function paramsOf(string $body): array
    {
        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/', $body, $m);

        return array_values(array_unique($m[1]));
    }

    /**
     * @return array<string, array<string, array{0: string, 1: string}>>
     */
    private function templates(): array
    {
        return [
            E::ProjectReceived->value => [
                'pt_BR' => ['Recebemos o seu projeto {{numero}}', 'Olá, {{nome}}! Recebemos o seu projeto {{numero}} para o {{veiculo}}. A {{oficina}} vai revisar e enviar o orçamento em breve.'],
                'es' => ['Recibimos tu proyecto {{numero}}', '¡Hola, {{nome}}! Recibimos tu proyecto {{numero}} para el {{veiculo}}. {{oficina}} lo revisará y te enviará la cotización pronto.'],
            ],
            E::QuoteSent->value => [
                'pt_BR' => ['Seu orçamento {{numero}} está pronto', 'Olá, {{nome}}! Seu orçamento {{numero}} ({{veiculo}}) ficou em {{total}}, válido até {{validade}}. Veja, aprove e pague o sinal aqui: {{link}}'],
                'es' => ['Tu cotización {{numero}} está lista', '¡Hola, {{nome}}! Tu cotización {{numero}} ({{veiculo}}) es de {{total}}, válida hasta el {{validade}}. Mírala, apruébala y paga el anticipo aquí: {{link}}'],
            ],
            E::QuoteReminder->value => [
                'pt_BR' => ['Seu orçamento {{numero}} está esperando', 'Olá, {{nome}}! O orçamento {{numero}} ({{total}}) vale até {{validade}}. Quer aprovar ou ajustar algo? {{link}}'],
                'es' => ['Tu cotización {{numero}} te espera', '¡Hola, {{nome}}! La cotización {{numero}} ({{total}}) vale hasta el {{validade}}. ¿Quieres aprobarla o ajustar algo? {{link}}'],
            ],
            E::QuoteApproved->value => [
                'pt_BR' => ['Orçamento {{numero}} aprovado', 'Obrigado, {{nome}}! O orçamento {{numero}} ({{total}}) foi aprovado. Sinal: {{sinal}}. Acompanhe aqui: {{link}}'],
                'es' => ['Cotización {{numero}} aprobada', '¡Gracias, {{nome}}! La cotización {{numero}} ({{total}}) quedó aprobada. Anticipo: {{sinal}}. Síguela aquí: {{link}}'],
            ],
            E::PaymentApproved->value => [
                'pt_BR' => ['Pagamento recebido', 'Olá, {{nome}}! Recebemos o pagamento de {{valor}} ({{tipo}}). Obrigado! {{link}}'],
                'es' => ['Pago recibido', '¡Hola, {{nome}}! Recibimos tu pago de {{valor}} ({{tipo}}). ¡Gracias! {{link}}'],
            ],
            E::PaymentRejected->value => [
                'pt_BR' => ['Pagamento não concluído', 'Olá, {{nome}}. O pagamento de {{valor}} ({{tipo}}) não foi concluído. Você pode tentar de novo aqui: {{link}}'],
                'es' => ['Pago no completado', 'Hola, {{nome}}. El pago de {{valor}} ({{tipo}}) no se completó. Puedes reintentarlo aquí: {{link}}'],
            ],
            E::AppointmentConfirmed->value => [
                'pt_BR' => ['Agendamento confirmado', 'Olá, {{nome}}! A recepção do veículo {{placa}} está marcada para {{data}} às {{hora}}. Endereço: {{endereco}}.'],
                'es' => ['Cita confirmada', '¡Hola, {{nome}}! La recepción del vehículo {{placa}} quedó para el {{data}} a las {{hora}}. Dirección: {{endereco}}.'],
            ],
            E::AppointmentMoved->value => [
                'pt_BR' => ['Agendamento alterado', 'Olá, {{nome}}! A recepção do veículo {{placa}} mudou para {{data}} às {{hora}}. Endereço: {{endereco}}.'],
                'es' => ['Cita modificada', '¡Hola, {{nome}}! La recepción del vehículo {{placa}} cambió al {{data}} a las {{hora}}. Dirección: {{endereco}}.'],
            ],
            E::AppointmentCancelled->value => [
                'pt_BR' => ['Agendamento cancelado', 'Olá, {{nome}}. A recepção do veículo {{placa}} em {{data}} às {{hora}} foi cancelada. Fale com a {{oficina}} para remarcar.'],
                'es' => ['Cita cancelada', 'Hola, {{nome}}. La recepción del vehículo {{placa}} del {{data}} a las {{hora}} fue cancelada. Escríbele a {{oficina}} para reprogramar.'],
            ],
            E::AppointmentReminder->value => [
                'pt_BR' => ['Lembrete: recepção do {{placa}}', 'Olá, {{nome}}! Lembrete: a recepção do veículo {{placa}} é em {{data}} às {{hora}}. Endereço: {{endereco}}.'],
                'es' => ['Recordatorio: recepción del {{placa}}', '¡Hola, {{nome}}! Recordatorio: la recepción del vehículo {{placa}} es el {{data}} a las {{hora}}. Dirección: {{endereco}}.'],
            ],
            E::CheckinReport->value => [
                'pt_BR' => ['Termo de recepção do veículo {{placa}}', 'Olá, {{nome}}! Recebemos o seu veículo {{placa}}. Este é o termo de recepção com o estado do veículo: {{link}}'],
                'es' => ['Acta de recepción del vehículo {{placa}}', '¡Hola, {{nome}}! Recibimos tu vehículo {{placa}}. Esta es el acta de recepción con su estado: {{link}}'],
            ],
            E::WorkOrderStage->value => [
                'pt_BR' => ['Novidades do seu {{placa}}', 'Olá, {{nome}}! O seu veículo {{placa}} passou para a etapa "{{etapa}}". Veja as fotos: {{link}}'],
                'es' => ['Novedades de tu {{placa}}', '¡Hola, {{nome}}! Tu vehículo {{placa}} pasó a la etapa "{{etapa}}". Mira las fotos: {{link}}'],
            ],
            E::WorkOrderReady->value => [
                'pt_BR' => ['Seu {{placa}} está pronto!', 'Olá, {{nome}}! O seu veículo {{placa}} está pronto para retirada. Saldo: {{saldo}}. Detalhes e pagamento: {{link}}'],
                'es' => ['¡Tu {{placa}} está listo!', '¡Hola, {{nome}}! Tu vehículo {{placa}} está listo para retirar. Saldo: {{saldo}}. Detalles y pago: {{link}}'],
            ],
            E::DeliveryReport->value => [
                'pt_BR' => ['Termo de entrega do veículo {{placa}}', 'Olá, {{nome}}! Obrigado pela confiança. Este é o termo de entrega do veículo {{placa}}, com os serviços e a garantia: {{link}}'],
                'es' => ['Acta de entrega del vehículo {{placa}}', '¡Hola, {{nome}}! Gracias por tu confianza. Esta es el acta de entrega del vehículo {{placa}}, con los servicios y la garantía: {{link}}'],
            ],
            E::WarrantyReview->value => [
                'pt_BR' => ['Revisão de garantia do {{placa}}', 'Olá, {{nome}}! A garantia do serviço no veículo {{placa}} vence em {{vencimento}}. Quer agendar uma revisão gratuita? {{link}}'],
                'es' => ['Revisión de garantía del {{placa}}', '¡Hola, {{nome}}! La garantía del trabajo en el vehículo {{placa}} vence el {{vencimento}}. ¿Quieres agendar una revisión sin costo? {{link}}'],
            ],
            E::ReviewRequest->value => [
                'pt_BR' => ['Como ficou o seu {{placa}}?', 'Olá, {{nome}}! Conte como ficou o seu veículo {{placa}}: sua avaliação ajuda a {{oficina}} a melhorar. {{link}}'],
                'es' => ['¿Cómo quedó tu {{placa}}?', '¡Hola, {{nome}}! Cuéntanos cómo quedó tu vehículo {{placa}}: tu opinión ayuda a {{oficina}} a mejorar. {{link}}'],
            ],
        ];
    }
}
