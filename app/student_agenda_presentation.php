<?php
declare(strict_types=1);
/** Only locale-specific display data. Never changes access, meeting status or capacity. */
function student_agenda_date_parts(string $utc,string $timezone='America/Sao_Paulo'): array {
    $empty=['day'=>'--','month'=>'','weekday'=>'','time'=>'--:--','full'=>'Data indisponível'];
    if(trim($utc)==='')return $empty;
    try{
        $dt=(new DateTimeImmutable($utc))->setTimezone(course_agenda_timezone($timezone));
        $months=['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
        $days=['dom','seg','ter','qua','qui','sex','sáb'];
        return [
            'day'=>$dt->format('d'),
            'month'=>$months[(int)$dt->format('n')-1],
            'weekday'=>$days[(int)$dt->format('w')],
            'time'=>$dt->format('H:i'),
            'full'=>course_agenda_when($utc,$timezone),
        ];
    }catch(Throwable){return $empty;}
}
function student_agenda_selected_view(mixed $value): string {
    return $value==='ouvintes'?'ouvintes':'encontros';
}
