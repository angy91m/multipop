<?php
defined( 'ABSPATH' ) || exit;
?>
<template v-if="['administrator', 'multipopolare_resp'].includes(profile.role) && profile.mpop_has_master_key">
    <button class="mpop-button" :disabled="masterkeyChanging || mkChangeErrors.length || !mkChangeFields.current" @click="changeMk">Cambia master key</button>
    <div id="mpop-mkChange">
        <input v-model="mkChangeFields.current" @input="staticMkErrors.length = 0" :class="mkChangeErrors.includes('current') ? 'bad-input' : ''" type="password" placeholder="Master key attuale"/>
        <input v-model="mkChangeFields.new" @input="staticMkErrors.length = 0" :class="mkChangeErrors.includes('new') ? 'bad-input' : ''" type="password" placeholder="Nuova master key"/>
        <input v-model="mkChangeFields.confirm" @input="staticMkErrors.length = 0" :class="mkChangeErrors.includes('confirm') ? 'bad-input' : ''" type="password" placeholder="Conferma master key"/>
    </div>
</template>
<template v-else>
    <div>
        Nessuna master key impostata
    </div>
</template>