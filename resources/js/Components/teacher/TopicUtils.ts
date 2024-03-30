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
}

export interface QOption {
    id?: number,
    question_id?: number,
    name: string,
    is_correct: boolean,
}
