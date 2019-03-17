<template>
    <div>
        <div class="row">
            <div class="col-12">
                <h1>{{ header }}</h1>
            </div>
        </div>
        <div v-for="(preview, index) in workouts">
            <workout-preview :workoutData="preview">
                <template slot="workoutDate">
                    <strong>Date: </strong> {{ preview.dateScheduled }}
                </template>
                <template slot="workoutName">
                    <strong>Name: </strong> {{ preview.name }}
                </template>
            </workout-preview>
        </div>
    </div>
</template>

<script>
    import WorkoutPreview from './WorkoutPreview';
    import * as types from '../store/types';

    export default {
        computed: {
            workouts() {
                return this.$store.getters[types.GET_WORKOUTS]
            }
        },
        data() {
            return {
                header: "Workouts"
            }
        },
        methods: {},
        components: {
            WorkoutPreview
        },
        mounted () {
            this.$store.dispatch(types.A_POPULATE_WORKOUTS);
        },
        beforeDestroy () {
            this.$store.dispatch(types.A_RESET_WORKOUT_STATE);
        },
    }
</script>