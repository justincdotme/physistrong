import LoginForm from "../components/LoginForm";
import Workouts from '../components/Workouts';
import Home from '../components/Home';
import Router from 'vue-router';
import Vue from 'vue';

Vue.use(Router);

const router = new Router( {
    mode: 'history',

    routes: [
        {
            path: '/',
            component: Home,
            name: 'home',
            meta: {
                requireAuth: true
            }
        },
        {
            path: '/workouts',
            component: Workouts,
            name: 'workouts',
            meta: {
                requireAuth: true
            }
        },
        {
            path: '/login',
            component: LoginForm,
            name: 'login'
        }
    ]
});

export default router;