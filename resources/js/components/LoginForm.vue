<template>
    <div class="login-form row">
        <div class="col-12">
            <div class="row">
                <div class="col-12">
                    <h1>Login</h1>
                </div>
            </div>
            <div v-show="error.hasError" class="row" id="login-error">
                <div class="col-12 col-md-6 offset-md-3 col-lg-4 offset-lg-4">
                    <div class="alert alert-danger" role="alert">
                        <strong>{{ error.title }}</strong> {{ error.message }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6 text-right">
                    <label for="email">
                        Email:
                    </label>
                </div>
                <div class="col-6">
                    <input id="email" name="email" type="email" v-model="user.email"/>
                </div>
            </div>
           <div class="row">
               <div class="col-6 text-right">
                   <label for="password">
                       Password:
                   </label>
               </div>
               <div class="col-6">
                   <input
                           id="password"
                           name="password"
                           type="password"
                           @keydown.enter="login({email: user.email, password: user.password})"
                           v-model="user.password"/>
               </div>
           </div>
           <div class="row">
               <div class="col-12 text-center">
                   <button id="submit" @click="login({email: user.email, password: user.password})">Submit</button>
               </div>
           </div>
        </div>
    </div>
</template>

<script>
    import * as types from '../store/types';

    export default {
        props: {},
        data() {
            return {
                user: {
                    email: '',
                    password: ''
                },
                error: {
                    hasError: false,
                    title: 'Oops!',
                    message: 'Invalid username or password'
                }
            }
        },
        methods: {
            login () {
                this.$store.dispatch(types.A_USER_LOGIN, {email: this.user.email, password: this.user.password})
                    .then((resolved) => {
                        this.error.hasError = false;
                    }, (error) => {
                        this.error.hasError = true;
                    });
            }
        },
    }
</script>