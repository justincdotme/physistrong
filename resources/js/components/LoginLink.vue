<template>
    <div>
        <router-link
                v-if="!userLoggedIn"
                class="nav-link"
                active-class="active"
                id="login"
                :to="{ name: 'login' }">Login</router-link>
        <a href="#"
           v-if="userLoggedIn"
           v-on:click="logout"
           id="logout"
           class="nav-link"
           active-class="active">Logout</a>
    </div>

</template>

<script>
    import * as types from '../store/types';

    export default {
        computed: {
            userLoggedIn () {
                return this.$store.getters[types.GET_USER_LOGGED_IN];
            }
        },
        props: {},
        methods: {
            logout () {
                this.$store.dispatch(types.A_USER_LOGOUT, {});
            }
        }
    };
</script>
