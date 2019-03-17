//state

//actions
export const A_POPULATE_WORKOUTS = 'workouts/populateWorkouts';
export const A_RESET_WORKOUT_STATE = 'workouts/aResetState';
export const A_USER_LOGIN = 'user/login';
export const A_USER_LOGOUT = 'user/logout';
export const A_AUTHENTICATE_USER = 'user/authenticateUser';
export const A_RESET_USER_STATE = 'user/aResetUserState';
export const A_SELECT_WORKOUT = 'workoutPreview/aResetUserState';


//mutations
export const M_WORKOUTS_LOADED = 'workouts/workoutsLoaded';
export const M_WORKOUTS_LOAD_FAILED = 'workouts/workoutsLoadFailed';
export const M_RESET_WORKOUTS_STATE = 'workouts/resetState';
export const M_USER_LOGGED_IN = 'user/userLoggedIn';
export const M_USER_LOGIN_FAILED = 'user/loginFailed';
export const M_USER_LOGGED_OUT = 'user/loggedOut';
export const M_RESET_USER_STATE = 'user/mResetState';
export const M_RESET_WORKOUT_PREVIEW_STATE = 'workoutPreview/resetWorkoutState';

//getters
export const GET_USER_LOGGED_IN = 'user/loggedIn';
export const GET_WORKOUTS = 'workouts/getWorkouts';
export const GET_WORKOUT_PAGINATION = 'workouts/getPagination';