<template>
    <div class="workout-preview row">
        <div class="col-3">
            <slot name="workoutDate"></slot>
        </div>
        <div class="col-7">
            <slot name="workoutName"></slot>
        </div>
        <div class="col-2">
            <button>Select Workout</button>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {}
        },
        methods: {},
    }
</script>