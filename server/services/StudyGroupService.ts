import { DatabaseStorage } from "../storage";
import type { StudyGroup, InsertStudyGroup, StudyGroupMember, StudyGroupWithDetails, StudyGroupMessage, InsertStudyGroupMessage, StudyGroupMessageWithDetails, Whiteboard, InsertWhiteboard } from "@shared/schema";

export class StudyGroupService {
  constructor(private storage: DatabaseStorage) {}

  /**
   * Create a new study group
   */
  async createStudyGroup(data: InsertStudyGroup): Promise<StudyGroup> {
    return await this.storage.createStudyGroup(data);
  }

  /**
   * Get all study groups for an establishment
   */
  async getStudyGroupsByEstablishment(establishmentId: string): Promise<StudyGroupWithDetails[]> {
    return await this.storage.getStudyGroupsByEstablishment(establishmentId);
  }

  /**
   * Get a specific study group by ID
   */
  async getStudyGroupById(groupId: string): Promise<StudyGroupWithDetails | null> {
    return await this.storage.getStudyGroupById(groupId);
  }

  /**
   * Join a study group
   */
  async joinStudyGroup(groupId: string, userId: string): Promise<StudyGroupMember> {
    return await this.storage.joinStudyGroup(groupId, userId);
  }

  /**
   * Get study group members
   */
  async getStudyGroupMembers(groupId: string) {
    return await this.storage.getStudyGroupMembers(groupId);
  }

  /**
   * Create a message in a study group
   */
  async createMessage(data: InsertStudyGroupMessage): Promise<StudyGroupMessageWithDetails> {
    return await this.storage.createMessage(data);
  }

  /**
   * Get messages from a study group
   */
  async getStudyGroupMessages(groupId: string, limit?: number): Promise<StudyGroupMessageWithDetails[]> {
    return await this.storage.getStudyGroupMessages(groupId, limit);
  }

  /**
   * Create a whiteboard for a study group
   */
  async createWhiteboard(data: InsertWhiteboard): Promise<Whiteboard> {
    return await this.storage.createWhiteboard(data);
  }

  /**
   * Get whiteboards for a study group
   */
  async getStudyGroupWhiteboards(groupId: string): Promise<Whiteboard[]> {
    return await this.storage.getStudyGroupWhiteboards(groupId);
  }

  /**
   * Update whiteboard content
   */
  async updateWhiteboard(whiteboardId: string, data: any): Promise<Whiteboard> {
    return await this.storage.updateWhiteboard(whiteboardId, data);
  }
}