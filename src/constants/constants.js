/*jshint esversion: 6 */
const Types = {
    Messages: {
      SYSTEM: 0,
      HELLO: 1,
      WELCOME: 2,
      MESSAGE: 3,
    },
    User:{
      LOGIN: 4,
      LOGIN_ANSWER: 5,
      INFO:  6,
      LOGOUT:  7,
    },
    Market:{
      INFO:   10,
      OFFER:  11,
      CHANGE: 12,
      KEEP:   15,
      ORDERS: 20,
    },
    Trade:{
      LIST:300,
      CANCEL:301,
      ENORDER:310,
    },
    Ping:9,
  }

  export default{
    Types
  }