<?php

declare(strict_types=1);

/**
 * Fixtures para testes de confirmação/rejeição na ConfirmationIntentRule.
 *
 * Formato: [message, is_affirmative, is_negative, tags[], notes]
 */
return [

    // ═══════════════════════════════════════════════════════════════
    // Affirmative strict (exact match)
    // ═══════════════════════════════════════════════════════════════

    ['sim', true, false, ['affirmative', 'strict'], 'sim — afirmativo clássico'],
    ['ss', true, false, ['affirmative', 'strict'], 'ss — abreviação WhatsApp'],
    ['ok', true, false, ['affirmative', 'strict'], 'ok — confirmação universal'],
    ['pode', true, false, ['affirmative', 'strict'], 'pode — autorização'],
    ['beleza', true, false, ['affirmative', 'strict'], 'beleza — gíria afirmativa'],
    ['blz', true, false, ['affirmative', 'strict'], 'blz — abreviação de beleza'],
    ['show', true, false, ['affirmative', 'strict'], 'show — gíria afirmativa'],
    ['perfeito', true, false, ['affirmative', 'strict'], 'perfeito — confirmação forte'],
    ['bora', true, false, ['affirmative', 'strict'], 'bora — vamos nessa'],
    ['confirma', true, false, ['affirmative', 'strict'], 'confirma — verbo direto'],
    ['sim por favor', true, false, ['affirmative', 'strict'], 'sim por favor — com cortesia'],
    ['sim!', true, false, ['affirmative', 'strict'], 'sim! — com pontuação enfática'],

    // ═══════════════════════════════════════════════════════════════
    // Affirmative loose (with trailing text)
    // ═══════════════════════════════════════════════════════════════

    ['sim, pode registrar', true, false, ['affirmative', 'loose'], 'sim + trailing instruction'],
    ['ok está bom', true, false, ['affirmative', 'loose'], 'ok + trailing approval'],
    ['pode sim', true, false, ['affirmative', 'loose'], 'pode + reforço afirmativo'],
    ['sim, manda ver', true, false, ['affirmative', 'loose'], 'sim + manda ver coloquial'],
    ['beleza, faz isso', true, false, ['affirmative', 'loose'], 'beleza + trailing instruction'],

    // ═══════════════════════════════════════════════════════════════
    // Negative strict
    // ═══════════════════════════════════════════════════════════════

    ['não', false, true, ['negative', 'strict'], 'não — negação clássica'],
    ['nn', false, true, ['negative', 'strict'], 'nn — abreviação WhatsApp'],
    ['cancela', false, true, ['negative', 'strict'], 'cancela — cancelamento direto'],
    ['não quero', false, true, ['negative', 'strict'], 'não quero — rejeição explícita'],
    ['esquece', false, true, ['negative', 'strict'], 'esquece — desistência coloquial'],
    ['deixa pra lá', false, true, ['negative', 'strict'], 'deixa pra lá — desistência'],
    ['negativo', false, true, ['negative', 'strict'], 'negativo — formal'],

    // ═══════════════════════════════════════════════════════════════
    // Negative loose (with trailing text)
    // ═══════════════════════════════════════════════════════════════

    ['não, obrigado', false, true, ['negative', 'loose'], 'não + cortesia'],
    ['cancela por favor', false, true, ['negative', 'loose'], 'cancela + por favor'],
    ['não precisa não', false, true, ['negative', 'loose'], 'dupla negação coloquial'],

    // ═══════════════════════════════════════════════════════════════
    // Neither (ambiguous — should be false for both)
    // ═══════════════════════════════════════════════════════════════

    ['talvez', false, false, ['ambiguous'], 'talvez — indefinido'],
    ['depois', false, false, ['ambiguous'], 'depois — adiamento, não confirmação'],
    ['não sei', false, false, ['ambiguous'], 'não sei — indecisão (não é negação)'],
    ['hmm', false, false, ['ambiguous'], 'hmm — onomatopeia de dúvida'],
];
