export const workoutsResponse = {
  "data": [
    {
      "type": "workout",
      "id": "3",
      "attributes": {
        "name": "Back & Calves",
        "date_scheduled": "2019-03-21"
      },
      "relationships": {
        "user": {
          "links": {
            "self": "http://api.physistrong.com/v1/users/1"
          }
        },
        "sets": {
          "links": {
            "self": "http://api.physistrong.com/v1/workouts/3/sets"
          }
        },
        "exercises": {
          "links": {
            "self": "http://api.physistrong.com/v1/workouts/3/exercises"
          }
        }
      },
      "links": {
        "self": "http://api.physistrong.com/v1/workouts/3"
      }
    },
    {
      "type": "workout",
      "id": "2",
      "attributes": {
        "name": "Chest & Shoulders",
        "date_scheduled": "2019-03-19"
      },
      "relationships": {
        "user": {
          "links": {
            "self": "http://api.physistrong.com/v1/users/1"
          }
        },
        "sets": {
          "links": {
            "self": "http://api.physistrong.com/v1/workouts/2/sets"
          }
        },
        "exercises": {
          "links": {
            "self": "http://api.physistrong.com/v1/workouts/2/exercises"
          }
        }
      },
      "links": {
        "self": "http://api.physistrong.com/v1/workouts/2"
      }
    },
    {
      "type": "workout",
      "id": "1",
      "attributes": {
        "name": "Leg Day",
        "date_scheduled": "2019-03-17"
      },
      "relationships": {
        "user": {
          "links": {
            "self": "http://api.physistrong.com/v1/users/1"
          }
        },
        "sets": {
          "links": {
            "self": "http://api.physistrong.com/v1/workouts/1/sets"
          }
        },
        "exercises": {
          "links": {
            "self": "http://api.physistrong.com/v1/workouts/1/exercises"
          }
        }
      },
      "links": {
        "self": "http://api.physistrong.com/v1/workouts/1"
      }
    }
  ],
  "links": {
    "first": "http://api.physistrong.com/v1/workouts?page=1",
    "last": "http://api.physistrong.com/v1/workouts?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://api.physistrong.com/v1/workouts",
    "per_page": 15,
    "to": 3,
    "total": 3
  }
};