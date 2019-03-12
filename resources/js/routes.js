import Workouts from './components/Workouts';

export default {
    mode: 'history',

    routes: [
        {
            path: '/',
            component: Workouts,
            name: 'workouts'
        }
    ]
}