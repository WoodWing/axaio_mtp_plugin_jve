/**
 * WorkflowMetaData.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class WorkflowMetaData  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty deadline;

    private java.lang.String urgency;

    private java.lang.String modifier;

    private java.util.Calendar modified;

    private java.lang.String creator;

    private java.util.Calendar created;

    private java.lang.String comment;

    private com.woodwing.enterprise.interfaces.services.wfl.State state;

    private java.lang.String routeTo;

    private java.lang.String lockedBy;

    private java.lang.String version;

    private com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty deadlineSoft;

    private org.apache.axis.types.UnsignedInt rating;

    private java.lang.String deletor;

    private java.util.Calendar deleted;

    public WorkflowMetaData() {
    }

    public WorkflowMetaData(
           com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty deadline,
           java.lang.String urgency,
           java.lang.String modifier,
           java.util.Calendar modified,
           java.lang.String creator,
           java.util.Calendar created,
           java.lang.String comment,
           com.woodwing.enterprise.interfaces.services.wfl.State state,
           java.lang.String routeTo,
           java.lang.String lockedBy,
           java.lang.String version,
           com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty deadlineSoft,
           org.apache.axis.types.UnsignedInt rating,
           java.lang.String deletor,
           java.util.Calendar deleted) {
           this.deadline = deadline;
           this.urgency = urgency;
           this.modifier = modifier;
           this.modified = modified;
           this.creator = creator;
           this.created = created;
           this.comment = comment;
           this.state = state;
           this.routeTo = routeTo;
           this.lockedBy = lockedBy;
           this.version = version;
           this.deadlineSoft = deadlineSoft;
           this.rating = rating;
           this.deletor = deletor;
           this.deleted = deleted;
    }


    /**
     * Gets the deadline value for this WorkflowMetaData.
     * 
     * @return deadline
     */
    public com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty getDeadline() {
        return deadline;
    }


    /**
     * Sets the deadline value for this WorkflowMetaData.
     * 
     * @param deadline
     */
    public void setDeadline(com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty deadline) {
        this.deadline = deadline;
    }


    /**
     * Gets the urgency value for this WorkflowMetaData.
     * 
     * @return urgency
     */
    public java.lang.String getUrgency() {
        return urgency;
    }


    /**
     * Sets the urgency value for this WorkflowMetaData.
     * 
     * @param urgency
     */
    public void setUrgency(java.lang.String urgency) {
        this.urgency = urgency;
    }


    /**
     * Gets the modifier value for this WorkflowMetaData.
     * 
     * @return modifier
     */
    public java.lang.String getModifier() {
        return modifier;
    }


    /**
     * Sets the modifier value for this WorkflowMetaData.
     * 
     * @param modifier
     */
    public void setModifier(java.lang.String modifier) {
        this.modifier = modifier;
    }


    /**
     * Gets the modified value for this WorkflowMetaData.
     * 
     * @return modified
     */
    public java.util.Calendar getModified() {
        return modified;
    }


    /**
     * Sets the modified value for this WorkflowMetaData.
     * 
     * @param modified
     */
    public void setModified(java.util.Calendar modified) {
        this.modified = modified;
    }


    /**
     * Gets the creator value for this WorkflowMetaData.
     * 
     * @return creator
     */
    public java.lang.String getCreator() {
        return creator;
    }


    /**
     * Sets the creator value for this WorkflowMetaData.
     * 
     * @param creator
     */
    public void setCreator(java.lang.String creator) {
        this.creator = creator;
    }


    /**
     * Gets the created value for this WorkflowMetaData.
     * 
     * @return created
     */
    public java.util.Calendar getCreated() {
        return created;
    }


    /**
     * Sets the created value for this WorkflowMetaData.
     * 
     * @param created
     */
    public void setCreated(java.util.Calendar created) {
        this.created = created;
    }


    /**
     * Gets the comment value for this WorkflowMetaData.
     * 
     * @return comment
     */
    public java.lang.String getComment() {
        return comment;
    }


    /**
     * Sets the comment value for this WorkflowMetaData.
     * 
     * @param comment
     */
    public void setComment(java.lang.String comment) {
        this.comment = comment;
    }


    /**
     * Gets the state value for this WorkflowMetaData.
     * 
     * @return state
     */
    public com.woodwing.enterprise.interfaces.services.wfl.State getState() {
        return state;
    }


    /**
     * Sets the state value for this WorkflowMetaData.
     * 
     * @param state
     */
    public void setState(com.woodwing.enterprise.interfaces.services.wfl.State state) {
        this.state = state;
    }


    /**
     * Gets the routeTo value for this WorkflowMetaData.
     * 
     * @return routeTo
     */
    public java.lang.String getRouteTo() {
        return routeTo;
    }


    /**
     * Sets the routeTo value for this WorkflowMetaData.
     * 
     * @param routeTo
     */
    public void setRouteTo(java.lang.String routeTo) {
        this.routeTo = routeTo;
    }


    /**
     * Gets the lockedBy value for this WorkflowMetaData.
     * 
     * @return lockedBy
     */
    public java.lang.String getLockedBy() {
        return lockedBy;
    }


    /**
     * Sets the lockedBy value for this WorkflowMetaData.
     * 
     * @param lockedBy
     */
    public void setLockedBy(java.lang.String lockedBy) {
        this.lockedBy = lockedBy;
    }


    /**
     * Gets the version value for this WorkflowMetaData.
     * 
     * @return version
     */
    public java.lang.String getVersion() {
        return version;
    }


    /**
     * Sets the version value for this WorkflowMetaData.
     * 
     * @param version
     */
    public void setVersion(java.lang.String version) {
        this.version = version;
    }


    /**
     * Gets the deadlineSoft value for this WorkflowMetaData.
     * 
     * @return deadlineSoft
     */
    public com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty getDeadlineSoft() {
        return deadlineSoft;
    }


    /**
     * Sets the deadlineSoft value for this WorkflowMetaData.
     * 
     * @param deadlineSoft
     */
    public void setDeadlineSoft(com.woodwing.enterprise.interfaces.services.wfl.DateTimeOrEmpty deadlineSoft) {
        this.deadlineSoft = deadlineSoft;
    }


    /**
     * Gets the rating value for this WorkflowMetaData.
     * 
     * @return rating
     */
    public org.apache.axis.types.UnsignedInt getRating() {
        return rating;
    }


    /**
     * Sets the rating value for this WorkflowMetaData.
     * 
     * @param rating
     */
    public void setRating(org.apache.axis.types.UnsignedInt rating) {
        this.rating = rating;
    }


    /**
     * Gets the deletor value for this WorkflowMetaData.
     * 
     * @return deletor
     */
    public java.lang.String getDeletor() {
        return deletor;
    }


    /**
     * Sets the deletor value for this WorkflowMetaData.
     * 
     * @param deletor
     */
    public void setDeletor(java.lang.String deletor) {
        this.deletor = deletor;
    }


    /**
     * Gets the deleted value for this WorkflowMetaData.
     * 
     * @return deleted
     */
    public java.util.Calendar getDeleted() {
        return deleted;
    }


    /**
     * Sets the deleted value for this WorkflowMetaData.
     * 
     * @param deleted
     */
    public void setDeleted(java.util.Calendar deleted) {
        this.deleted = deleted;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof WorkflowMetaData)) return false;
        WorkflowMetaData other = (WorkflowMetaData) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.deadline==null && other.getDeadline()==null) || 
             (this.deadline!=null &&
              this.deadline.equals(other.getDeadline()))) &&
            ((this.urgency==null && other.getUrgency()==null) || 
             (this.urgency!=null &&
              this.urgency.equals(other.getUrgency()))) &&
            ((this.modifier==null && other.getModifier()==null) || 
             (this.modifier!=null &&
              this.modifier.equals(other.getModifier()))) &&
            ((this.modified==null && other.getModified()==null) || 
             (this.modified!=null &&
              this.modified.equals(other.getModified()))) &&
            ((this.creator==null && other.getCreator()==null) || 
             (this.creator!=null &&
              this.creator.equals(other.getCreator()))) &&
            ((this.created==null && other.getCreated()==null) || 
             (this.created!=null &&
              this.created.equals(other.getCreated()))) &&
            ((this.comment==null && other.getComment()==null) || 
             (this.comment!=null &&
              this.comment.equals(other.getComment()))) &&
            ((this.state==null && other.getState()==null) || 
             (this.state!=null &&
              this.state.equals(other.getState()))) &&
            ((this.routeTo==null && other.getRouteTo()==null) || 
             (this.routeTo!=null &&
              this.routeTo.equals(other.getRouteTo()))) &&
            ((this.lockedBy==null && other.getLockedBy()==null) || 
             (this.lockedBy!=null &&
              this.lockedBy.equals(other.getLockedBy()))) &&
            ((this.version==null && other.getVersion()==null) || 
             (this.version!=null &&
              this.version.equals(other.getVersion()))) &&
            ((this.deadlineSoft==null && other.getDeadlineSoft()==null) || 
             (this.deadlineSoft!=null &&
              this.deadlineSoft.equals(other.getDeadlineSoft()))) &&
            ((this.rating==null && other.getRating()==null) || 
             (this.rating!=null &&
              this.rating.equals(other.getRating()))) &&
            ((this.deletor==null && other.getDeletor()==null) || 
             (this.deletor!=null &&
              this.deletor.equals(other.getDeletor()))) &&
            ((this.deleted==null && other.getDeleted()==null) || 
             (this.deleted!=null &&
              this.deleted.equals(other.getDeleted())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getDeadline() != null) {
            _hashCode += getDeadline().hashCode();
        }
        if (getUrgency() != null) {
            _hashCode += getUrgency().hashCode();
        }
        if (getModifier() != null) {
            _hashCode += getModifier().hashCode();
        }
        if (getModified() != null) {
            _hashCode += getModified().hashCode();
        }
        if (getCreator() != null) {
            _hashCode += getCreator().hashCode();
        }
        if (getCreated() != null) {
            _hashCode += getCreated().hashCode();
        }
        if (getComment() != null) {
            _hashCode += getComment().hashCode();
        }
        if (getState() != null) {
            _hashCode += getState().hashCode();
        }
        if (getRouteTo() != null) {
            _hashCode += getRouteTo().hashCode();
        }
        if (getLockedBy() != null) {
            _hashCode += getLockedBy().hashCode();
        }
        if (getVersion() != null) {
            _hashCode += getVersion().hashCode();
        }
        if (getDeadlineSoft() != null) {
            _hashCode += getDeadlineSoft().hashCode();
        }
        if (getRating() != null) {
            _hashCode += getRating().hashCode();
        }
        if (getDeletor() != null) {
            _hashCode += getDeletor().hashCode();
        }
        if (getDeleted() != null) {
            _hashCode += getDeleted().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(WorkflowMetaData.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "WorkflowMetaData"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deadline");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Deadline"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("urgency");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Urgency"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("modifier");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Modifier"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("modified");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Modified"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("creator");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Creator"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("created");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Created"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("comment");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Comment"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("state");
        elemField.setXmlName(new javax.xml.namespace.QName("", "State"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "State"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("routeTo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RouteTo"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("lockedBy");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LockedBy"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("version");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Version"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deadlineSoft");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DeadlineSoft"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rating");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rating"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deletor");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Deletor"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deleted");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Deleted"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
