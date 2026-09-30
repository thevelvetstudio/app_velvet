<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationDiscardedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public string $stage,
    ) {}

    public function envelope(): Envelope
    {
        $copy = $this->stageCopy();

        return new Envelope(
            subject: $copy['subject'].' · The Velvet Studio',
        );
    }

    public function content(): Content
    {
        $copy = $this->stageCopy();

        return new Content(
            view: 'emails.application-discarded',
            with: array_merge($copy, [
                'applicantName' => $this->lead->full_name,
                'discardedAt' => $this->lead->updated_at,
                'discardMessage' => $copy['message'],
            ]),
        );
    }

    private function stageCopy(): array
    {
        return self::stageCopies()[$this->stage] ?? self::stageCopies()['DEFAULT'];
    }

    private static function stageCopies(): array
    {
        return [
            'NEW' => [
                'stageLabel' => 'Revisión inicial',
                'subject' => 'Gracias por compartir tu historia',
                'headline' => 'Tu historia sigue teniendo mucho por contar.',
                'message' => 'Gracias por confiar en The Velvet Studio y permitirnos conocer tu perfil. En esta ocasión no continuaremos con tu aplicación en nuestra convocatoria actual.',
                'encouragement' => 'Que esta decisión no apague tu brillo: cada experiencia suma y tu talento merece seguir encontrando espacios para crecer.',
                'nextSteps' => 'Muy pronto abriremos nuevas vacantes. Nos encantará que vuelvas a aplicar y sigamos construyendo nuevas historias juntos.',
            ],
            'CONTACTED' => [
                'stageLabel' => 'Contacto inicial',
                'subject' => 'Gracias por conversar con Velvet',
                'headline' => 'Gracias por abrirnos un espacio.',
                'message' => 'Agradecemos el tiempo que compartiste con nuestro equipo durante el contacto inicial. Después de revisar esta convocatoria, no avanzaremos con tu aplicación por ahora.',
                'encouragement' => 'Tu disposición y tu historia dejan una huella. Las oportunidades también se construyen con cada conversación.',
                'nextSteps' => 'Mantente atento(a) a nuestras próximas vacantes: esperamos volver a encontrarnos en una oportunidad que conecte contigo.',
            ],
            'QUALIFIED' => [
                'stageLabel' => 'Perfil calificado',
                'subject' => 'Una pausa en este proceso, nuevas oportunidades por venir',
                'headline' => 'Tu perfil nos dejó una buena impresión.',
                'message' => 'Luego de revisar tu información con cuidado, hemos decidido cerrar tu participación en esta convocatoria específica.',
                'encouragement' => 'Ser parte de una selección ya habla de tu potencial. Sigue creyendo en lo que puedes construir.',
                'nextSteps' => 'Próximamente abriremos nuevas vacantes y nos encantará considerar nuevamente tu perfil.',
            ],
            'CONVERTED' => [
                'stageLabel' => 'Conversión a candidato',
                'subject' => 'Gracias por avanzar con Velvet',
                'headline' => 'Tu perfil llegó a una etapa importante.',
                'message' => 'Después de revisar el proceso actual, debemos cerrar esta convocatoria específica y no continuaremos con tu aplicación en esta ocasión.',
                'encouragement' => 'El camino que recorriste demuestra tu interés y tu potencial. Sigue apostando por tu crecimiento.',
                'nextSteps' => 'Próximamente abriremos nuevas vacantes y esperamos volver a encontrarnos para construir nuevas historias juntos.',
            ],
            'UNRESPONSIVE' => [
                'stageLabel' => 'Seguimiento',
                'subject' => 'Dejamos esta oportunidad en pausa',
                'headline' => 'A veces el momento también importa.',
                'message' => 'No logramos completar el contacto dentro de los tiempos de esta convocatoria, así que cerraremos este proceso por ahora.',
                'encouragement' => 'Esto no define tu talento ni tus posibilidades. Cada etapa puede abrir la puerta a un nuevo comienzo.',
                'nextSteps' => 'Cuando abramos nuevas vacantes, podrás volver a postularte. Nos encantará saber de ti nuevamente.',
            ],
            'PREQUALIFIED' => [
                'stageLabel' => 'Precalificación',
                'subject' => 'Gracias por avanzar con Velvet',
                'headline' => 'Gracias por dar el siguiente paso.',
                'message' => 'Valoramos el tiempo y la energía que invertiste en nuestra etapa de precalificación. En esta ocasión no continuaremos con tu aplicación.',
                'encouragement' => 'Tu crecimiento no depende de una sola respuesta. Sigue preparándote y cuidando ese talento que te hace especial.',
                'nextSteps' => 'Pronto tendremos nuevas vacantes. Esperamos verte de nuevo y seguir creciendo juntos.',
            ],
            'INTERVIEW' => [
                'stageLabel' => 'Entrevista',
                'subject' => 'Gracias por tu tiempo y tu confianza',
                'headline' => 'Fue un gusto conocerte mejor.',
                'message' => 'Gracias por participar en nuestra entrevista y compartir tu experiencia con tanta apertura. Después de esta revisión, no avanzaremos en la convocatoria actual.',
                'encouragement' => 'Tu voz, tu experiencia y tu autenticidad tienen valor. Una decisión puntual nunca resume todo lo que puedes aportar.',
                'nextSteps' => 'Seguiremos abriendo oportunidades y nos encantará volver a encontrarnos en una próxima vacante.',
            ],
            'EVALUATION' => [
                'stageLabel' => 'Evaluación',
                'subject' => 'Gracias por ser parte de nuestra evaluación',
                'headline' => 'Tu esfuerzo también cuenta.',
                'message' => 'Agradecemos tu compromiso durante la etapa de evaluación. En esta oportunidad no podremos continuar con el proceso de selección.',
                'encouragement' => 'Todo lo que aprendiste en el camino se queda contigo. Sigue avanzando con confianza y determinación.',
                'nextSteps' => 'Abriremos nuevas vacantes próximamente. Cuando llegue el momento, estaremos felices de recibir una nueva aplicación tuya.',
            ],
            'ADMITTED' => [
                'stageLabel' => 'Admisión',
                'subject' => 'Una actualización sobre tu proceso en Velvet',
                'headline' => 'Gracias por llegar hasta aquí.',
                'message' => 'Después de revisar la disponibilidad de esta convocatoria, hemos tenido que cerrar tu participación en este proceso específico.',
                'encouragement' => 'Llegar hasta esta etapa demuestra tu compromiso y el valor de tu perfil. Sigue confiando en tu camino.',
                'nextSteps' => 'Te invitamos a estar pendiente de nuestras próximas oportunidades para volver a crecer juntos.',
            ],
            'WAITING' => [
                'stageLabel' => 'Lista de espera',
                'subject' => 'Una actualización sobre tu proceso',
                'headline' => 'Gracias por tu paciencia y tu confianza.',
                'message' => 'La convocatoria actual ha llegado a su cierre y, por esta ocasión, no podremos continuar con tu aplicación desde la lista de espera.',
                'encouragement' => 'Tu perfil sigue siendo valioso. A veces una pausa solo prepara el espacio para una mejor oportunidad.',
                'nextSteps' => 'Mantente atento(a): próximamente abriremos nuevas vacantes y nos encantará volver a saber de ti.',
            ],
            'ONBOARDING' => [
                'stageLabel' => 'Onboarding',
                'subject' => 'Una actualización importante de tu proceso',
                'headline' => 'Gracias por haber avanzado con nosotros.',
                'message' => 'Por ajustes de esta convocatoria, debemos cerrar tu proceso actual antes de completar el onboarding.',
                'encouragement' => 'Tu talento y el camino recorrido siguen teniendo valor. Los procesos pueden cambiar, pero tu potencial permanece.',
                'nextSteps' => 'Cuando abramos nuevas vacantes, esperamos volver a encontrarte y construir juntos una nueva oportunidad.',
            ],
            'CONTRACTING' => [
                'stageLabel' => 'Contratación',
                'subject' => 'Una actualización sobre tu proceso en Velvet',
                'headline' => 'Gracias por tu confianza durante este proceso.',
                'message' => 'Después de revisar las condiciones de la convocatoria actual, no podremos continuar con tu proceso de contratación en esta ocasión.',
                'encouragement' => 'Tu experiencia y tu dedicación dejan una marca positiva. Sigue apostando por todo lo que puedes lograr.',
                'nextSteps' => 'Abriremos nuevas oportunidades próximamente y nos encantará considerar nuevamente tu perfil.',
            ],
            'INDUCTION' => [
                'stageLabel' => 'Inducción',
                'subject' => 'Gracias por tu proceso con The Velvet Studio',
                'headline' => 'Tu recorrido con nosotros también cuenta.',
                'message' => 'Por cambios en la convocatoria, debemos cerrar este proceso durante la etapa de inducción.',
                'encouragement' => 'Gracias por tu disposición y por permitirnos acompañarte. Lo aprendido en el camino también es crecimiento.',
                'nextSteps' => 'Estaremos abriendo nuevas vacantes. Esperamos que podamos volver a encontrarnos muy pronto.',
            ],
            'READY_TO_ACTIVATE' => [
                'stageLabel' => 'Activación',
                'subject' => 'Una actualización sobre tu proceso',
                'headline' => 'Gracias por llegar tan lejos.',
                'message' => 'La convocatoria actual ha sido cerrada y, en esta oportunidad, no podremos completar la activación de tu perfil.',
                'encouragement' => 'Todo el camino recorrido demuestra tu compromiso. Sigue creyendo en tu talento y en las oportunidades que vienen.',
                'nextSteps' => 'Próximamente anunciaremos nuevas vacantes. Nos alegrará recibir nuevamente tu aplicación.',
            ],
            'ACTIVE' => [
                'stageLabel' => 'Perfil activo',
                'subject' => 'Una actualización de tu relación con Velvet',
                'headline' => 'Gracias por todo lo que compartimos.',
                'message' => 'Hemos cerrado esta etapa de tu perfil dentro de The Velvet Studio. Queremos agradecerte por la confianza y el camino recorrido.',
                'encouragement' => 'Tu talento, tu esfuerzo y tu historia siguen siendo importantes para nosotros.',
                'nextSteps' => 'Cuando surjan nuevas oportunidades, esperamos volver a encontrarnos y seguir creciendo juntos.',
            ],
            'WITHDRAWN' => [
                'stageLabel' => 'Retiro del proceso',
                'subject' => 'Gracias por tu tiempo con Velvet',
                'headline' => 'Respetamos el momento de tu decisión.',
                'message' => 'Registramos el cierre de tu proceso actual y queremos agradecerte por la confianza que depositaste en The Velvet Studio.',
                'encouragement' => 'Tu camino sigue abierto y tu talento tiene muchas formas de brillar.',
                'nextSteps' => 'Si en el futuro deseas volver a aplicar, estaremos felices de recibirte y explorar nuevas posibilidades juntos.',
            ],
            'DEFAULT' => [
                'stageLabel' => 'Proceso de selección',
                'subject' => 'Gracias por compartir tu historia',
                'headline' => 'Tu historia sigue adelante.',
                'message' => 'Gracias por tu interés en The Velvet Studio. En esta ocasión no continuaremos con tu aplicación dentro de la convocatoria actual.',
                'encouragement' => 'Una respuesta no define tu talento ni todo lo que puedes lograr.',
                'nextSteps' => 'Próximamente abriremos nuevas vacantes y nos encantará que vuelvas a encontrarnos.',
            ],
        ];
    }
}
