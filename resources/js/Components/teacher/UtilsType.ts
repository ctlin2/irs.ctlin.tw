export interface Topic {
    id: number,
    name: string,
    parent_id: number,
    level: number,
    mark_id: number,
}

export interface Question {
    id?: number,
    name: string,
    question_type_id: number,
    topic_id: number,
    media_url?: string, // added by C.T.Lin
    media_type?: string, // added by C.T.Lin
    answer?: string, // added by C.T.Lin
}

export interface QOption {
    id?: number,
    question_id?: number,
    name: string,
    is_correct?: boolean,
    media_type?: string, // added by C.T.Lin
}

export interface Course {
    id: number,
    class_name: string,
    course_name: string,
    // def_g_point: number,
    // def_s_point: number,
    // def_point_step: number,
    // att_status_1: number,
    // att_status_2: number,
    // att_status_3: number,
    // att_status_4: number,
    // att_status_5: number,
}

export interface Student {
    id: number,
    std_id: number,
    group_id: number,
    group_no: number,
    status: number,
    course_id: number,
    course_date: string,
    s_point: number,
    std_no: string,
    std_name: string,
}

export interface Group {
    id: number,
    group_id: number,
    course_id: number,
    course_date: number,
    g_point: number,
    no: number,
}

export interface QuizList {
    id: number,
    course_id: number,
    course_date: string,
    question_id: number,
    name: string,
    std_answer_num?: number,
    std_answer_correct_num?: number,
    expired_at: string,
}

export interface CourseQuiz {
    id: number,
    course_id: number,
    course_date: string,
    expired_at: string,
    question_id: number,
    marks: number, // C.T.Lin
}

export interface Answers {
    id: number,
    course_quiz_id: number,
    std_id: number,
    question_option_id: number, // q_option_id
    answer: string, // C.T.Lin, for fill-in question
}