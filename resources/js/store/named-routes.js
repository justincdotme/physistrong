export const ziggy = {
    namedRoutes: {
        "workouts.index": {"uri": "v1\/workouts", "methods": ["GET", "HEAD"], "domain": "api.physistrong.srv"},
        "workouts.store": {"uri": "v1\/workouts", "methods": ["POST"], "domain": "api.physistrong.srv"},
        "workouts.show": {
            "uri": "v1\/workouts\/{workout}",
            "methods": ["GET", "HEAD"],
            "domain": "api.physistrong.srv"
        },
        "workouts.update": {
            "uri": "v1\/workouts\/{workout}",
            "methods": ["PUT", "PATCH"],
            "domain": "api.physistrong.srv"
        },
        "users.show": {"uri": "v1\/users\/{user}", "methods": ["GET", "HEAD"], "domain": "api.physistrong.srv"},
        "workouts.exercises.store": {
            "uri": "v1\/workouts\/{workout}\/exercises\/{exercise}",
            "methods": ["POST"],
            "domain": "api.physistrong.srv"
        },
        "workouts.exercises.index": {
            "uri": "v1\/workouts\/{workout}\/exercises",
            "methods": ["GET", "HEAD"],
            "domain": "api.physistrong.srv"
        },
        "workouts.exercises.destroy": {
            "uri": "v1\/workouts\/{workout}\/exercises\/{exercise}",
            "methods": ["DELETE"],
            "domain": "api.physistrong.srv"
        },
        "workouts.exercises.sets.index": {
            "uri": "v1\/workouts\/{workout}\/exercises\/{exercise}\/sets",
            "methods": ["GET", "HEAD"],
            "domain": "api.physistrong.srv"
        },
        "workouts.exercises.sets.store": {
            "uri": "v1\/workouts\/{workout}\/exercises\/{exercise}\/sets",
            "methods": ["POST"],
            "domain": "api.physistrong.srv"
        },
        "sets.show": {"uri": "v1\/sets\/{set}", "methods": ["GET", "HEAD"], "domain": "api.physistrong.srv"},
        "sets.update": {"uri": "v1\/sets\/{set}", "methods": ["PUT", "PATCH"], "domain": "api.physistrong.srv"},
        "sets.destroy": {"uri": "v1\/sets\/{set}", "methods": ["DELETE"], "domain": "api.physistrong.srv"},
        "workouts.sets.index": {
            "uri": "v1\/workouts\/{workout}\/sets",
            "methods": ["GET", "HEAD"],
            "domain": "api.physistrong.srv"
        },
        "exercises.store": {"uri": "v1\/exercises", "methods": ["POST"], "domain": "api.physistrong.srv"},
        "exercises.show": {
            "uri": "v1\/exercises\/{exercise}",
            "methods": ["GET", "HEAD"],
            "domain": "api.physistrong.srv"
        },
        "exercises.update": {
            "uri": "v1\/exercises\/{exercise}",
            "methods": ["PUT", "PATCH"],
            "domain": "api.physistrong.srv"
        },
        "user.store": {"uri": "v1\/user\/register", "methods": ["POST"], "domain": "api.physistrong.srv"},
        "user.login": {"uri": "v1\/user\/login", "methods": ["POST"], "domain": "api.physistrong.srv"},
        "password.request": {"uri": "v1\/password\/email", "methods": ["POST"], "domain": "api.physistrong.srv"},
        "password.reset": {"uri": "v1\/password\/reset\/{token}", "methods": ["POST"], "domain": "api.physistrong.srv"}
    },
    baseUrl: 'http://127.0.0.1:8000/',
    baseProtocol: 'http',
    baseDomain: '127.0.0.1',
    basePort: 8000,
    defaultParameters: []
}